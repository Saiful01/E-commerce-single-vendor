<?php

namespace App\Console\Commands;

use App\Exceptions\OrderException;
use App\Models\Order;
use App\Services\Couriers\SteadfastService;
use Illuminate\Console\Command;

/**
 * The webhook does the work in real time. This is the safety net: it asks Steadfast about every parcel
 * that is still on its way, so an update is never lost if the webhook was missed or not set up.
 *
 *   php artisan couriers:sync-steadfast
 */
class SyncSteadfastStatuses extends Command
{
    protected $signature   = 'couriers:sync-steadfast';
    protected $description = 'Check the status of Steadfast parcels that are still on their way';

    public function handle(): int
    {
        $courier = SteadfastService::activeCourier();
        if (! $courier) {
            $this->warn('Steadfast is not set up or switched off.');
            return self::SUCCESS;
        }

        $service = new SteadfastService($courier);
        $checked = 0;
        $failed  = 0;

        Order::query()
            ->whereNotNull('courier_consignment_id')
            ->where('courier_booked_at', '>', now()->subDays(30))
            ->where(fn ($q) => $q->whereNull('courier_status')->orWhereNotIn('courier_status', SteadfastService::FINAL))
            ->chunkById(50, function ($orders) use ($service, &$checked, &$failed) {
                foreach ($orders as $order) {
                    try {
                        $service->refresh($order);
                        $checked++;
                    } catch (OrderException $e) {
                        $failed++;
                        $this->warn("{$order->order_number}: {$e->getMessage()}");
                    }
                    usleep(200000);   // be gentle with the API
                }
            });

        $this->info("Checked {$checked} parcels, {$failed} failed.");

        return self::SUCCESS;
    }
}
