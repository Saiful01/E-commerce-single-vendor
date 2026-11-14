<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function index(): View
    {
        $gateways = PaymentGateway::ordered()->get();

        $items = $gateways->map(function (PaymentGateway $g) {
            $creds = $g->credentials ?? [];

            return [
                'id'           => $g->id,
                'name'         => $g->name,
                'driver'       => $g->driver,
                'driver_label' => $g->driver_label,
                'type'         => $g->type,
                'type_label'   => PaymentGateway::TYPES[$g->type],
                'logo'         => $g->logo_url,
                'instructions' => $g->instructions,
                'sandbox_mode' => $g->sandbox_mode,
                'fee_type'     => $g->fee_type,
                'fee_value'    => $g->fee_value,
                'fee_label'    => $g->fee_label,
                'min_amount'   => $g->min_amount,
                'max_amount'   => $g->max_amount,
                'is_active'    => $g->is_active,
                'configured'   => $g->isConfigured(),
                'orders_count' => $g->ordersCount(),
                // Plain values for non-secret fields, only "is it saved" for secret ones
                'credentials'  => collect(PaymentGateway::DRIVERS[$g->driver]['fields'])->mapWithKeys(fn ($f) => [
                    $f['key'] => $f['secret'] ? '' : ($creds[$f['key']] ?? ''),
                ])->all(),
                'saved'        => collect($creds)->filter()->keys()->values()->all(),
            ];
        })->values();

        return view('admin.payment-gateways.index', [
            'gateways' => $gateways,
            'items'    => $items,
            'drivers'  => PaymentGateway::DRIVERS,
            'types'    => PaymentGateway::TYPES,
            'feeTypes' => PaymentGateway::FEE_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));

        $gateway = new PaymentGateway($this->payload($request, $data));
        $gateway->sort_order  = (int) PaymentGateway::max('sort_order') + 1;
        $gateway->credentials = $this->mergeCredentials(null, $data['driver'], $request->input('credentials', []));
        $gateway->logo        = $request->hasFile('logo') ? $request->file('logo')->store('payment-gateways', config('filesystems.default')) : null;

        // A method that is not fully set up cannot be switched on
        if ($gateway->is_active && ! $gateway->isConfigured()) {
            return back()->withInput()->with('error', 'Fill in every field for this payment method before switching it on.');
        }

        $gateway->save();

        return redirect()->route('admin.payment-gateways.index')->with('success', 'Payment method added.');
    }

    public function update(Request $request, PaymentGateway $paymentGateway): RedirectResponse
    {
        $data = $request->validate($this->rules($request, $paymentGateway));

        $paymentGateway->fill($this->payload($request, $data));
        $paymentGateway->credentials = $this->mergeCredentials($paymentGateway, $data['driver'], $request->input('credentials', []));

        if ($paymentGateway->is_active && ! $paymentGateway->isConfigured()) {
            return back()->withInput()->with('error', 'Fill in every field for this payment method before switching it on.');
        }

        if ($request->hasFile('logo')) {
            $this->deleteLogo($paymentGateway->logo);
            $paymentGateway->logo = $request->file('logo')->store('payment-gateways', config('filesystems.default'));
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($paymentGateway->logo);
            $paymentGateway->logo = null;
        }

        $paymentGateway->save();

        return redirect()->route('admin.payment-gateways.index')->with('success', 'Payment method updated.');
    }

    public function destroy(PaymentGateway $paymentGateway): RedirectResponse
    {
        $orders = $paymentGateway->ordersCount();

        if ($orders > 0) {
            return back()->with('error', "{$orders} " . Str::plural('order', $orders) . ' were paid with this method. Switch it off instead of deleting it.');
        }

        $this->deleteLogo($paymentGateway->logo);
        $paymentGateway->delete();

        return back()->with('success', 'Payment method deleted.');
    }

    /** PATCH /admin/payment-gateways/{payment_gateway}/toggle */
    public function toggle(PaymentGateway $paymentGateway): JsonResponse
    {
        if (! $paymentGateway->is_active && ! $paymentGateway->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Fill in every field for this payment method before switching it on.'], 422);
        }

        $paymentGateway->update(['is_active' => ! $paymentGateway->is_active]);

        return response()->json([
            'success' => true,
            'value'   => $paymentGateway->is_active,
            'message' => $paymentGateway->is_active ? 'Payment method switched on.' : 'Payment method switched off.',
        ]);
    }

    /** POST /admin/payment-gateways/reorder   body: { order: [id, id, ...] } */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:payment_gateways,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('order') as $index => $id) {
                PaymentGateway::whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /* ------------------------------ helpers ------------------------------ */

    private function rules(Request $request, ?PaymentGateway $gateway = null): array
    {
        return [
            'name'          => ['required', 'string', 'max:100', Rule::unique('payment_gateways', 'name')->ignore($gateway?->id)],
            'driver'        => ['required', Rule::in(array_keys(PaymentGateway::DRIVERS))],
            'logo'          => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'remove_logo'   => ['nullable', 'boolean'],
            'instructions'  => ['nullable', 'string', 'max:500'],
            'fee_type'      => ['required', Rule::in(array_keys(PaymentGateway::FEE_TYPES))],
            'fee_value'     => [$request->input('fee_type', 'none') === 'none' ? 'nullable' : 'required', 'numeric', 'min:0', $request->input('fee_type') === 'percentage' ? 'max:100' : 'max:1000000'],
            'min_amount'    => ['nullable', 'numeric', 'min:0'],
            'max_amount'    => ['nullable', 'numeric', 'min:0', $request->filled('min_amount') ? 'gte:min_amount' : 'min:0'],
            'credentials'   => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function payload(Request $request, array $data): array
    {
        $feeType = $data['fee_type'];

        return [
            'name'         => trim($data['name']),
            'driver'       => $data['driver'],
            'instructions' => $data['instructions'] ?? null,
            'sandbox_mode' => $request->boolean('sandbox_mode'),
            'fee_type'     => $feeType,
            'fee_value'    => $feeType === 'none' ? 0 : ($data['fee_value'] ?? 0),
            'min_amount'   => $data['min_amount'] ?? null,
            'max_amount'   => $data['max_amount'] ?? null,
            'is_active'    => $request->boolean('is_active'),
        ];
    }

    /**
     * Keep only the fields this driver uses. A blank box keeps the value already saved,
     * so secrets never have to be typed again. Switching driver starts clean.
     */
    private function mergeCredentials(?PaymentGateway $gateway, string $driver, array $input): array
    {
        $existing = ($gateway && $gateway->driver === $driver) ? ($gateway->credentials ?? []) : [];
        $merged   = [];

        foreach (PaymentGateway::DRIVERS[$driver]['fields'] as $field) {
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
