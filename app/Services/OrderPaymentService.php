<?php

namespace App\Services;

use App\Events\OrderNotification;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Every payment and refund goes through here. orders.paid_amount and orders.payment_status
 * are always worked out from the payment list, never typed by hand.
 */
class OrderPaymentService
{
    /**
     * Add a payment. A confirmed one counts straight away.
     * A pending one is a customer saying "I paid", and an admin checks it later.
     *
     * @throws OrderException
     */
    public function record(
        Order $order, float $amount, ?int $gatewayId = null, ?string $transactionId = null, ?string $note = null,
        ?User $by = null, bool $confirmed = true, string $source = 'admin'
    ): OrderPayment {
        return DB::transaction(function () use ($order, $amount, $gatewayId, $transactionId, $note, $by, $confirmed, $source) {
            $order  = $this->lock($order);
            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw new OrderException('Enter an amount greater than zero.');
            }
            $this->assertOpen($order);
            $this->assertWithinDue($order, $amount);

            $payment = $order->payments()->create([
                'payment_gateway_id' => $gatewayId,
                'type'               => 'payment',
                'status'             => $confirmed ? 'confirmed' : 'pending',
                'amount'             => $amount,
                'transaction_id'     => $transactionId ?: null,
                'note'               => $note ?: null,
                'source'             => $source,
                'user_id'            => $by?->id,
                'confirmed_by'       => $confirmed ? $by?->id : null,
                'confirmed_at'       => $confirmed ? now() : null,
            ]);

            if ($transactionId) {
                $order->update(['transaction_id' => $transactionId]);
            }
            if ($confirmed) {
                $this->sync($order);
            }

            $key = $confirmed ? 'payment.recorded' : 'payment.claimed';
            DB::afterCommit(fn () => event(new OrderNotification($key, $order->fresh(), ['payment_id' => $payment->id, 'amount' => $amount])));

            return $payment;
        });
    }

    /** @throws OrderException */
    public function confirm(OrderPayment $payment, ?float $amount = null, ?User $by = null): OrderPayment
    {
        return DB::transaction(function () use ($payment, $amount, $by) {
            $payment = OrderPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== 'pending') {
                throw new OrderException('This payment was already handled.');
            }

            $order  = $this->lock($payment->order);
            $amount = round($amount ?? $payment->amount, 2);

            if ($amount <= 0) {
                throw new OrderException('Enter an amount greater than zero.');
            }
            $this->assertOpen($order);
            $this->assertWithinDue($order, $amount);

            $payment->update(['status' => 'confirmed', 'amount' => $amount, 'confirmed_by' => $by?->id, 'confirmed_at' => now()]);
            $this->sync($order);

            DB::afterCommit(fn () => event(new OrderNotification('payment.recorded', $order->fresh(), ['payment_id' => $payment->id, 'amount' => $amount])));

            return $payment;
        });
    }

    /** @throws OrderException */
    public function reject(OrderPayment $payment, ?string $reason = null, ?User $by = null): OrderPayment
    {
        return DB::transaction(function () use ($payment, $reason, $by) {
            $payment = OrderPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== 'pending') {
                throw new OrderException('This payment was already handled.');
            }

            $payment->update([
                'status'       => 'rejected',
                'confirmed_by' => $by?->id,
                'note'         => trim(($payment->note ? $payment->note . ' | ' : '') . 'Rejected' . ($reason ? ': ' . $reason : '')),
            ]);

            $order = $payment->order;
            DB::afterCommit(fn () => event(new OrderNotification('payment.rejected', $order->fresh(), ['payment_id' => $payment->id, 'reason' => $reason])));

            return $payment;
        });
    }

    /** Money given back to the customer. Cannot be more than what was paid. @throws OrderException */
    public function refund(
        Order $order, float $amount, ?int $gatewayId = null, ?string $transactionId = null, ?string $note = null, ?User $by = null
    ): OrderPayment {
        return DB::transaction(function () use ($order, $amount, $gatewayId, $transactionId, $note, $by) {
            $order  = $this->lock($order);
            $amount = round($amount, 2);

            if ($amount <= 0) {
                throw new OrderException('Enter an amount greater than zero.');
            }
            if ($amount > $order->paid_amount + 0.009) {
                throw new OrderException('You can refund at most ' . SiteSetting::current()->formatMoney($order->paid_amount) . ', the amount paid so far.');
            }

            $refund = $order->payments()->create([
                'payment_gateway_id' => $gatewayId,
                'type'               => 'refund',
                'status'             => 'confirmed',
                'amount'             => $amount,
                'transaction_id'     => $transactionId ?: null,
                'note'               => $note ?: null,
                'source'             => 'admin',
                'user_id'            => $by?->id,
                'confirmed_by'       => $by?->id,
                'confirmed_at'       => now(),
            ]);

            $this->sync($order);

            return $refund;
        });
    }

    /** paid_amount = confirmed payments - confirmed refunds. payment_status follows from it. */
    public function sync(Order $order): Order
    {
        $paid     = round((float) $order->payments()->confirmed()->where('type', 'payment')->sum('amount'), 2);
        $refunded = round((float) $order->payments()->confirmed()->where('type', 'refund')->sum('amount'), 2);
        $net      = round($paid - $refunded, 2);
        $payable  = round($order->total - $order->returned_total, 2);

        $status = match (true) {
            $refunded > 0 && $net <= 0.009                  => 'refunded',
            $payable > 0.009 && $net >= $payable - 0.009    => 'paid',
            $net > 0.009                                    => 'partial',
            default                                         => 'unpaid',
        };

        $order->update(['paid_amount' => max($net, 0), 'payment_status' => $status]);

        return $order;
    }

    /* ------------------------------ internals ------------------------------ */

    private function lock(Order $order): Order
    {
        return Order::whereKey($order->id)->lockForUpdate()->with('status')->firstOrFail();
    }

    private function assertOpen(Order $order): void
    {
        if ($order->status->restock_items) {
            throw new OrderException('This order is cancelled or returned, so it cannot take payments.');
        }
    }

    private function assertWithinDue(Order $order, float $amount): void
    {
        if ($amount > $order->due_amount + 0.009) {
            throw new OrderException('That is more than the amount still due (' . SiteSetting::current()->formatMoney($order->due_amount) . ').');
        }
    }
}
