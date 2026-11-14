<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * One event for everything that may need an SMS or email.
 * Keys: order.placed, order.status_changed, payment.recorded, payment.claimed, payment.rejected,
 *       return.requested, return.approved, return.rejected, return.completed
 *
 * Nothing listens yet. The SMS / email listener is the next step.
 */
class OrderNotification
{
    use Dispatchable;

    public function __construct(public string $key, public Order $order, public array $context = [])
    {
    }
}
