<?php

namespace App\Services\Payments\Concerns;

use App\Exceptions\OrderException;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Services\OrderPaymentService;
use Illuminate\Support\Facades\Log;

trait RecordsOnlinePayment
{
    /**
     * Puts a payment the gateway confirmed into the order. Call it only after the gateway itself said "paid".
     * A payment that cannot be applied (order cancelled meanwhile ...) is never lost: it is marked "review"
     * and written into the order's internal note.
     */
    protected function recordPaid(OnlinePayment $attempt, array $fields, ?string $reference, string $note, bool $confirmed = true): OnlinePayment
    {
        if (in_array($attempt->status, ['paid', 'review'], true)) {
            return $attempt;
        }

        $order = Order::findOrFail($attempt->order_id);

        try {
            app(OrderPaymentService::class)->record(
                $order, $attempt->amount, $attempt->payment_gateway_id, $reference, $note, null, $confirmed, 'gateway'
            );
            $attempt->update($fields + ['status' => $confirmed ? 'paid' : 'review']);
        } catch (OrderException $e) {
            $attempt->update($fields + ['status' => 'review']);
            $order->update(['admin_note' => trim(($order->admin_note ? $order->admin_note . "\n" : '')
                . "Online payment {$attempt->tran_id} of {$attempt->amount} was received but could not be added: {$e->getMessage()}")]);
            Log::error('Online payment received but not applied', ['tran_id' => $attempt->tran_id, 'reason' => $e->getMessage()]);
        }

        return $attempt;
    }

    protected function failAttempt(OnlinePayment $attempt, string $status, array $response): OnlinePayment
    {
        $attempt->update(['status' => $status, 'response' => $response]);

        return $attempt;
    }
}
