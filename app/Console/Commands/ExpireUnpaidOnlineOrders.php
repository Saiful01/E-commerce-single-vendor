<?php

namespace App\Console\Commands;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * An online order that was never paid keeps its items out of stock. This cancels such orders
 * after a few hours so the stock comes back.
 *
 *   php artisan orders:expire-online
 */
class ExpireUnpaidOnlineOrders extends Command
{
    protected $signature   = 'orders:expire-online';
    protected $description = 'Cancel online-payment orders that were never paid and put their items back in stock';

    public function handle(OrderService $orders): int
    {
        $cancelled = OrderStatus::where('slug', 'cancelled')->first();
        if (! $cancelled) {
            $this->warn('There is no "cancelled" order status, nothing done.');
            return self::SUCCESS;
        }

        $hours = (int) config('shop.online_payment_hold_hours', 3);
        $count = 0;

        Order::query()
            ->with('status')
            ->where('payment_status', 'unpaid')
            ->where('created_at', '<', now()->subHours($hours))
            ->whereHas('paymentGateway', fn ($q) => $q->whereIn('driver', array_keys(\App\Support\OnlineGateways::DRIVERS)))
            ->whereDoesntHave('payments', fn ($q) => $q->whereIn('status', ['confirmed', 'pending']))
            ->whereHas('status', fn ($q) => $q->where('is_default', true))      // still untouched by the team
            ->chunkById(100, function ($list) use ($orders, $cancelled, $hours, &$count) {
                foreach ($list as $order) {
                    try {
                        $orders->changeStatus($order, $cancelled, "Online payment was not completed within {$hours} hours.");
                        $count++;
                    } catch (OrderException $e) {
                        $this->warn("{$order->order_number}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Cancelled {$count} unpaid online " . ($count === 1 ? 'order.' : 'orders.'));

        return self::SUCCESS;
    }
}
