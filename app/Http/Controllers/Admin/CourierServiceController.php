<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourierServiceController extends Controller
{
    public function index(): View
    {
        $couriers = CourierService::orderByDesc('is_default')->orderBy('name')->get();

        $items = $couriers->map(function (CourierService $c) {
            $creds = $c->credentials ?? [];

            return [
                'id'            => $c->id,
                'name'          => $c->name,
                'driver'        => $c->driver,
                'driver_label'  => $c->driver_label,
                'logo'          => $c->logo_url,
                'phone'         => $c->phone,
                'email'         => $c->email,
                'website'       => $c->website,
                'tracking_url'  => $c->tracking_url,
                'delivery_time' => $c->delivery_time,
                'coverage_note' => $c->coverage_note,
                'sandbox_mode'  => $c->sandbox_mode,
                'is_default'    => $c->is_default,
                'is_active'     => $c->is_active,
                'configured'    => $c->isConfigured(),
                'orders_count'  => $c->ordersCount(),
                // Plain values for non-secret fields, only "is it saved" for secret ones
                'credentials'   => collect(CourierService::DRIVERS[$c->driver]['fields'])->mapWithKeys(fn ($f) => [
                    $f['key'] => $f['secret'] ? '' : ($creds[$f['key']] ?? ''),
                ])->all(),
                'saved'         => collect($creds)->filter()->keys()->values()->all(),
            ];
        })->values();

        return view('admin.courier-services.index', [
            'couriers' => $couriers,
            'items'    => $items,
            'drivers'  => CourierService::DRIVERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));

        DB::transaction(function () use ($request, $data) {
            if ($request->boolean('is_default')) {
                CourierService::where('is_default', true)->update(['is_default' => false]);
            }

            CourierService::create($this->payload($request, $data) + [
                    'logo'        => $request->hasFile('logo') ? $request->file('logo')->store('couriers', config('filesystems.default')) : null,
                    'credentials' => $this->mergeCredentials(null, $data['driver'], $request->input('credentials', [])),
                ]);
        });

        return redirect()->route('admin.courier-services.index')->with('success', 'Courier added.');
    }

    public function update(Request $request, CourierService $courierService): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $courierService));

        DB::transaction(function () use ($request, $data, $courierService) {
            if ($request->boolean('is_default')) {
                CourierService::where('id', '!=', $courierService->id)->where('is_default', true)->update(['is_default' => false]);
            }

            $payload = $this->payload($request, $data) + [
                    'credentials' => $this->mergeCredentials($courierService, $data['driver'], $request->input('credentials', [])),
                ];

            if ($request->hasFile('logo')) {
                $this->deleteLogo($courierService->logo);
                $payload['logo'] = $request->file('logo')->store('couriers', config('filesystems.default'));
            } elseif ($request->boolean('remove_logo')) {
                $this->deleteLogo($courierService->logo);
                $payload['logo'] = null;
            }

            $courierService->update($payload);
        });

        return redirect()->route('admin.courier-services.index')->with('success', 'Courier updated.');
    }

    public function destroy(CourierService $courierService): RedirectResponse
    {
        $orders = $courierService->ordersCount();

        if ($orders > 0) {
            return back()->with('error', "{$orders} " . Str::plural('order', $orders) . ' used this courier. Switch it off instead of deleting it.');
        }

        $this->deleteLogo($courierService->logo);
        $courierService->delete();

        return back()->with('success', 'Courier deleted.');
    }

    /** PATCH /admin/courier-services/{courier_service}/toggle */
    public function toggle(CourierService $courierService): JsonResponse
    {
        if (! $courierService->is_active && ! $courierService->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Fill in the API credentials before switching this courier on.'], 422);
        }

        $courierService->update(['is_active' => ! $courierService->is_active]);

        return response()->json([
            'success' => true,
            'value'   => $courierService->is_active,
            'message' => $courierService->is_active ? 'Courier switched on.' : 'Courier switched off.',
        ]);
    }

    /* ------------------------------ helpers ------------------------------ */

    private function rules(Request $request, ?CourierService $courier = null): array
    {
        return [
            'name'          => ['required', 'string', 'max:100', Rule::unique('courier_services', 'name')->ignore($courier?->id)],
            'driver'        => ['required', Rule::in(array_keys(CourierService::DRIVERS))],
            'logo'          => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'remove_logo'   => ['nullable', 'boolean'],
            'phone'         => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'email'         => ['nullable', 'email', 'max:100'],
            'website'       => ['nullable', 'url', 'max:255'],
            'tracking_url'  => ['nullable', 'url', 'max:255'],
            'delivery_time' => ['nullable', 'string', 'max:60'],
            'coverage_note' => ['nullable', 'string', 'max:1000'],
            'credentials'   => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'name'          => trim($data['name']),
            'driver'        => $data['driver'],
            'phone'         => $data['phone'] ?? null,
            'email'         => $data['email'] ?? null,
            'website'       => $data['website'] ?? null,
            'tracking_url'  => $data['tracking_url'] ?? null,
            'delivery_time' => $data['delivery_time'] ?? null,
            'coverage_note' => $data['coverage_note'] ?? null,
            'sandbox_mode'  => $request->boolean('sandbox_mode'),
            'is_default'    => $request->boolean('is_default'),
            'is_active'     => $request->boolean('is_active'),
        ];
    }

    /**
     * Keep only the fields this driver uses. A blank box keeps the value already saved,
     * so secrets never have to be typed again. Switching driver starts clean.
     */
    private function mergeCredentials(?CourierService $courier, string $driver, array $input): array
    {
        $existing = ($courier && $courier->driver === $driver) ? ($courier->credentials ?? []) : [];
        $merged   = [];

        foreach (CourierService::DRIVERS[$driver]['fields'] as $field) {
            $key   = $field['key'];
            $value = trim((string) ($input[$key] ?? ''));

            $merged[$key] = $value !== '' ? $value : ($existing[$key] ?? null);
        }

        return array_filter($merged, fn ($v) => $v !== null && $v !== '');
    }

    private function deleteLogo(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk(config('filesystems.default'))->delete($path);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
