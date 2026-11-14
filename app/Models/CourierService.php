<?php

namespace App\Models;

use App\Models\Concerns\ResolvesStorageUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourierService extends Model
{
    use ResolvesStorageUrl;

    /**
     * Supported integrations and the credentials each one needs.
     * secret = true means the value is never sent back to the browser.
     * optional = true means the courier counts as "set up" even when this one is empty.
     */
    public const DRIVERS = [
        'manual' => ['label' => 'Manual (no API)', 'fields' => []],
        'steadfast' => ['label' => 'Steadfast', 'fields' => [
            ['key' => 'api_key',       'label' => 'API key',    'secret' => true],
            ['key' => 'secret_key',    'label' => 'Secret key', 'secret' => true],
            // From the Steadfast dashboard > Webhook > "Generate". Needed for automatic status updates.
            ['key' => 'webhook_token', 'label' => 'Webhook auth token', 'secret' => true, 'optional' => true],
        ]],
        'pathao' => ['label' => 'Pathao', 'fields' => [
            ['key' => 'client_id',     'label' => 'Client ID',     'secret' => false],
            ['key' => 'client_secret', 'label' => 'Client secret', 'secret' => true],
            ['key' => 'username',      'label' => 'Username',      'secret' => false],
            ['key' => 'password',      'label' => 'Password',      'secret' => true],
            ['key' => 'store_id',      'label' => 'Store ID',      'secret' => false],
        ]],
        'redx' => ['label' => 'RedX', 'fields' => [
            ['key' => 'access_token', 'label' => 'Access token', 'secret' => true],
        ]],
        'paperfly' => ['label' => 'Paperfly', 'fields' => [
            ['key' => 'username', 'label' => 'Username', 'secret' => false],
            ['key' => 'password', 'label' => 'Password', 'secret' => true],
            ['key' => 'api_key',  'label' => 'API key',  'secret' => true],
        ]],
        'ecourier' => ['label' => 'eCourier', 'fields' => [
            ['key' => 'api_key',    'label' => 'API key',    'secret' => true],
            ['key' => 'api_secret', 'label' => 'API secret', 'secret' => true],
            ['key' => 'user_id',    'label' => 'User ID',    'secret' => false],
        ]],
    ];

    protected $fillable = [
        'name', 'driver', 'logo', 'phone', 'email', 'website', 'tracking_url',
        'delivery_time', 'coverage_note', 'credentials', 'sandbox_mode', 'is_default', 'is_active',
    ];

    protected $hidden = ['credentials'];

    protected $casts = [
        'credentials'  => 'encrypted:array',
        'sandbox_mode' => 'boolean',
        'is_default'   => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->storageUrl($this->logo);
    }

    public function getDriverLabelAttribute(): string
    {
        return self::DRIVERS[$this->driver]['label'] ?? $this->driver;
    }

    /** True when every credential the driver needs has a value (manual couriers need none, optional fields can stay empty). */
    public function isConfigured(): bool
    {
        $creds = $this->credentials ?? [];

        foreach (self::DRIVERS[$this->driver]['fields'] ?? [] as $field) {
            if (! empty($field['optional'])) {
                continue;
            }
            if (empty($creds[$field['key']])) {
                return false;
            }
        }

        return true;
    }

    /** Public tracking link for a consignment, or null when no template is set. */
    public function trackingUrlFor(string $trackingCode): ?string
    {
        return $this->tracking_url
            ? str_replace('{tracking_code}', rawurlencode($trackingCode), $this->tracking_url)
            : null;
    }

    /** Orders that use this courier. Returns 0 until the orders table exists. */
    public function ordersCount(): int
    {
        static $ready = null;
        $ready ??= Schema::hasTable('orders') && Schema::hasColumn('orders', 'courier_service_id');

        return $ready ? DB::table('orders')->where('courier_service_id', $this->id)->count() : 0;
    }
}
