<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentGateway extends Model
{
    use ResolvesStorageUrl;

    /**
     * type: offline (paid on delivery or by bank) | wallet (mobile wallet, manual or API) | online (card / gateway)
     * secret = true means the value is never sent back to the browser.
     */
    public const DRIVERS = [
        'cod' => ['label' => 'Cash on delivery', 'type' => 'offline', 'fields' => []],

        'bank_transfer' => ['label' => 'Bank transfer', 'type' => 'offline', 'fields' => [
            ['key' => 'bank_name',      'label' => 'Bank name',      'secret' => false],
            ['key' => 'account_name',   'label' => 'Account name',   'secret' => false],
            ['key' => 'account_number', 'label' => 'Account number', 'secret' => false],
            ['key' => 'branch',         'label' => 'Branch',         'secret' => false],
        ]],

        'bkash_manual' => ['label' => 'bKash (send money)', 'type' => 'wallet', 'fields' => [
            ['key' => 'account_number', 'label' => 'bKash number',                 'secret' => false],
            ['key' => 'account_type',   'label' => 'Account type (Personal, Agent)', 'secret' => false],
        ]],
        'nagad_manual' => ['label' => 'Nagad (send money)', 'type' => 'wallet', 'fields' => [
            ['key' => 'account_number', 'label' => 'Nagad number',                 'secret' => false],
            ['key' => 'account_type',   'label' => 'Account type (Personal, Agent)', 'secret' => false],
        ]],
        'rocket_manual' => ['label' => 'Rocket (send money)', 'type' => 'wallet', 'fields' => [
            ['key' => 'account_number', 'label' => 'Rocket number',                'secret' => false],
            ['key' => 'account_type',   'label' => 'Account type (Personal, Agent)', 'secret' => false],
        ]],

        'bkash' => ['label' => 'bKash (payment API)', 'type' => 'wallet', 'fields' => [
            ['key' => 'app_key',    'label' => 'App key',    'secret' => true],
            ['key' => 'app_secret', 'label' => 'App secret', 'secret' => true],
            ['key' => 'username',   'label' => 'Username',   'secret' => false],
            ['key' => 'password',   'label' => 'Password',   'secret' => true],
        ]],
        'nagad' => ['label' => 'Nagad (payment API)', 'type' => 'wallet', 'fields' => [
            ['key' => 'merchant_id',     'label' => 'Merchant ID',     'secret' => false],
            ['key' => 'merchant_number', 'label' => 'Merchant number', 'secret' => false],
            ['key' => 'public_key',      'label' => 'Public key',      'secret' => true],
            ['key' => 'private_key',     'label' => 'Private key',     'secret' => true],
        ]],

        'sslcommerz' => ['label' => 'SSLCommerz', 'type' => 'online', 'fields' => [
            ['key' => 'store_id',       'label' => 'Store ID',       'secret' => false],
            ['key' => 'store_password', 'label' => 'Store password', 'secret' => true],
        ]],
        'stripe' => ['label' => 'Stripe', 'type' => 'online', 'fields' => [
            ['key' => 'publishable_key', 'label' => 'Publishable key', 'secret' => false],
            ['key' => 'secret_key',      'label' => 'Secret key',      'secret' => true],
            ['key' => 'webhook_secret',  'label' => 'Webhook secret',  'secret' => true],
        ]],
    ];

    public const TYPES = ['offline' => 'Offline', 'wallet' => 'Mobile wallet', 'online' => 'Online'];

    public const FEE_TYPES = [
        'none'       => 'No fee',
        'percentage' => 'Percentage of the order',
        'fixed'      => 'Fixed amount',
    ];

    protected $fillable = [
        'name', 'driver', 'logo', 'instructions', 'credentials', 'sandbox_mode',
        'fee_type', 'fee_value', 'min_amount', 'max_amount', 'sort_order', 'is_active',
    ];

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials'  => 'encrypted:array',
        'sandbox_mode' => 'boolean',
        'is_active'    => 'boolean',
        'fee_value'    => 'float',
        'min_amount'   => 'float',
        'max_amount'   => 'float',
        'sort_order'   => 'integer',
    ];

    /* ------------------------------ scopes ------------------------------ */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /* ------------------------------ accessors ------------------------------ */

    public function getLogoUrlAttribute(): ?string
    {
        return $this->storageUrl($this->logo);
    }

    public function getDriverLabelAttribute(): string
    {
        return self::DRIVERS[$this->driver]['label'] ?? $this->driver;
    }

    public function getTypeAttribute(): string
    {
        return self::DRIVERS[$this->driver]['type'] ?? 'offline';
    }

    public function getFeeLabelAttribute(): string
    {
        $money = SiteSetting::current();

        return match ($this->fee_type) {
            'percentage' => rtrim(rtrim(number_format($this->fee_value, 2, '.', ''), '0'), '.') . '% fee',
            'fixed'      => $money->formatMoney($this->fee_value) . ' fee',
            default      => 'No fee',
        };
    }

    /* ------------------------------ helpers ------------------------------ */

    /** True when every value the driver needs has been filled in (COD needs none). */
    public function isConfigured(): bool
    {
        $creds = $this->credentials ?? [];

        foreach (self::DRIVERS[$this->driver]['fields'] ?? [] as $field) {
            if (empty($creds[$field['key']])) {
                return false;
            }
        }

        return true;
    }

    /** Checkout helper: can a customer pay this order total with this method? */
    public function isAvailableFor(float $orderTotal): bool
    {
        return $this->is_active
            && $this->isConfigured()
            && ($this->min_amount === null || $orderTotal >= $this->min_amount)
            && ($this->max_amount === null || $orderTotal <= $this->max_amount);
    }

    /** Extra charge this method adds to an order total. */
    public function feeFor(float $orderTotal): float
    {
        return round(match ($this->fee_type) {
            'percentage' => $orderTotal * $this->fee_value / 100,
            'fixed'      => $this->fee_value,
            default      => 0,
        }, 2);
    }

    /** Orders paid with this method. Returns 0 until the orders table exists. */
    public function ordersCount(): int
    {
        static $ready = null;
        $ready ??= Schema::hasTable('orders') && Schema::hasColumn('orders', 'payment_gateway_id');

        return $ready ? DB::table('orders')->where('payment_gateway_id', $this->id)->count() : 0;
    }
}
