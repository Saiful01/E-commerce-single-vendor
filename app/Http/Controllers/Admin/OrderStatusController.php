<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderStatusController extends Controller
{
    public function index(): View
    {
        $statuses = OrderStatus::ordered()->get();

        $items = $statuses->map(fn (OrderStatus $s) => [
            'id'              => $s->id,
            'name'            => $s->name,
            'slug'            => $s->slug,
            'color'           => $s->color,
            'description'     => $s->description,
            'is_default'      => $s->is_default,
            'is_final'        => $s->is_final,
            'restock_items'   => $s->restock_items,
            'notify_customer' => $s->notify_customer,
            'is_system'       => $s->is_system,
            'is_active'       => $s->is_active,
            'orders_count'    => $s->ordersCount(),
        ])->values();

        return view('admin.order-statuses.index', compact('statuses', 'items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        $this->guardFlags($request);

        DB::transaction(function () use ($request, $data) {
            if ($request->boolean('is_default')) {
                OrderStatus::where('is_default', true)->update(['is_default' => false]);
            }

            OrderStatus::create($this->payload($request, $data) + [
                    'slug'       => $this->uniqueSlug($data['name']),
                    'sort_order' => (int) OrderStatus::max('sort_order') + 1,
                    'is_system'  => false,
                ]);
        });

        return redirect()->route('admin.order-statuses.index')->with('success', 'Status added.');
    }

    public function update(Request $request, OrderStatus $orderStatus): RedirectResponse
    {
        $data = $request->validate($this->rules($orderStatus));
        $this->guardFlags($request);

        // There must always be one default status
        if ($orderStatus->is_default && ! $request->boolean('is_default')) {
            return back()->withInput()->with('error', 'Pick another status as the default first. There must always be one.');
        }

        DB::transaction(function () use ($request, $data, $orderStatus) {
            if ($request->boolean('is_default')) {
                OrderStatus::where('id', '!=', $orderStatus->id)->where('is_default', true)->update(['is_default' => false]);
            }

            $orderStatus->update($this->payload($request, $data)); // slug never changes
        });

        return redirect()->route('admin.order-statuses.index')->with('success', 'Status updated.');
    }

    public function destroy(OrderStatus $orderStatus): RedirectResponse
    {
        if ($orderStatus->is_system) {
            return back()->with('error', "\"{$orderStatus->name}\" is a built-in status. You can switch it off instead of deleting it.");
        }
        if ($orderStatus->is_default) {
            return back()->with('error', 'The default status cannot be deleted.');
        }

        $orders = $orderStatus->ordersCount();
        if ($orders > 0) {
            return back()->with('error', "{$orders} " . Str::plural('order', $orders) . ' still use this status.');
        }

        $orderStatus->delete();

        return back()->with('success', 'Status deleted.');
    }

    /** PATCH /admin/order-statuses/{order_status}/toggle */
    public function toggle(OrderStatus $orderStatus): JsonResponse
    {
        if ($orderStatus->is_default) {
            return response()->json(['success' => false, 'message' => 'The default status cannot be switched off.'], 422);
        }

        $orderStatus->update(['is_active' => ! $orderStatus->is_active]);

        return response()->json([
            'success' => true,
            'value'   => $orderStatus->is_active,
            'message' => $orderStatus->is_active ? 'Status switched on.' : 'Status switched off.',
        ]);
    }

    /** POST /admin/order-statuses/reorder   body: { order: [id, id, ...] } */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'order'   => 'required|array|min:1',
            'order.*' => 'integer|exists:order_statuses,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->input('order') as $index => $id) {
                OrderStatus::whereKey($id)->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Order saved.']);
    }

    /* ------------------------------ helpers ------------------------------ */

    private function rules(?OrderStatus $status = null): array
    {
        return [
            'name'        => ['required', 'string', 'max:60', Rule::unique('order_statuses', 'name')->ignore($status?->id)],
            'color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Combinations that do not make sense. */
    private function guardFlags(Request $request): void
    {
        if ($request->boolean('is_default') && $request->boolean('is_final')) {
            abort(back()->withInput()->with('error', 'The default status cannot be a final status.'));
        }
        if ($request->boolean('is_default') && ! $request->boolean('is_active')) {
            abort(back()->withInput()->with('error', 'The default status must be active.'));
        }
    }

    private function payload(Request $request, array $data): array
    {
        return [
            'name'            => trim($data['name']),
            'color'           => strtoupper($data['color']),
            'description'     => $data['description'] ?? null,
            'is_default'      => $request->boolean('is_default'),
            'is_final'        => $request->boolean('is_final'),
            'restock_items'   => $request->boolean('restock_items'),
            'notify_customer' => $request->boolean('notify_customer'),
            'is_active'       => $request->boolean('is_active'),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base      = Str::slug($name) ?: 'status';
        $candidate = $base;
        $i         = 2;

        while (OrderStatus::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }
}
