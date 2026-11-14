<?php

namespace App\Console\Commands;

use App\Models\OnlinePayment;
use App\Services\Payments\BkashService;
use App\Services\Payments\NagadService;
use Illuminate\Console\Command;

/**
 * A customer can pay and then close the browser before coming back to the shop.
 * This asks bKash / Nagad about every payment that was started but never finished,
 * and records the ones that were really paid.
 *
 *   php artisan payments:reconcile
 */
class ReconcileOnlinePayments extends Command
{
    protected $signature   = 'payments:reconcile';
    protected $description = 'Check bKash and Nagad payments that were started but never confirmed';

    public function handle(): int
    {
        $checked = 0;
        $paid    = 0;

        OnlinePayment::with('gateway')
            ->where('status', 'initiated')
            ->whereNotNull('provider_ref')
            ->where('created_at', '<', now()->subMinutes(5))     // give the customer time to finish
            ->where('created_at', '>', now()->subHours(24))
            ->whereHas('gateway', fn ($q) => $q->whereIn('driver', ['bkash', 'nagad']))
            ->chunkById(50, function ($attempts) use (&$checked, &$paid) {
                foreach ($attempts as $attempt) {
                    $service = $attempt->gateway->driver === 'bkash' ? new BkashService($attempt->gateway) : new NagadService($attempt->gateway);

                    try {
                        $checked++;
                        $paid += (int) in_array($service->handleCallback($attempt, [])->status, ['paid', 'review'], true);
                    } catch (\Throwable $e) {
                        $this->warn("{$attempt->tran_id}: {$e->getMessage()}");
                    }
                }
            });

        $this->info("Checked {$checked}, found {$paid} paid.");

        return self::SUCCESS;
    }
}
