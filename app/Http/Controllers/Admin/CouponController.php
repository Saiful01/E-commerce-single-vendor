<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    private const STATE_LABELS = [
        'live'      => 'Live',
        'scheduled' => 'Scheduled',
        'expired'   => 'Expired',
        'exhausted' => 'Used up',
        'inactive'  => 'Inactive',
    ];

    /* ---------------------------------------------------------------
     |  INDEX
     * --------------------------------------------------------------*/
    public function index(Request $request): View
    {
        $q     = trim((string) $request->query('q', ''));
        $state = in_array($request->query('state'), Coupon::STATES, true) ? $request->query('state') : null;

        $coupons = Coupon::query()
            ->with(['categories:id,name', 'brands:id,name', 'products:id,name'])
            ->withSum('usages as total_discount', 'discount_amount')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('code', 'like', '%' . strtoupper($q) . '%')
                ->orWhere('name', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")))
            ->when($state, fn ($query) => $query->state($state))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Last 5 uses of each coupon on this page
        $recentUses = CouponUsage::query()
            ->whereIn('coupon_id', $coupons->pluck('id'))
            ->with('user:id,name')
            ->latest()
            ->get()
            ->groupBy('coupon_id')
            ->map(fn ($uses) => $uses->take(5)->map(fn (CouponUsage $u) => [
                'user'   => $u->user?->name ?? 'Guest',
                'amount' => $u->discount_amount,
                'date'   => $u->created_at?->format('d M Y, h:i A'),
            ])->values());

        $details = $coupons->getCollection()->map(fn (Coupon $c) => [
            'id'              => $c->id,
            'code'            => $c->code,
            'name'            => $c->name,
            'description'     => $c->description,
            'state'           => $c->state,
            'state_label'     => self::STATE_LABELS[$c->state],
            'type'            => Coupon::TYPES[$c->type] ?? $c->type,
            'discount_label'  => $c->discount_label,
            'min_order'       => $c->min_order_label,
            'usage_label'     => $c->usage_label,
            'usage_limit'     => $c->usage_limit,
            'per_user_limit'  => $c->usage_limit_per_user,
            'first_order_only'   => $c->first_order_only,
            'exclude_discounted' => $c->exclude_discounted,
            'applies_to'      => $c->applies_to,
            'applies_label'   => Coupon::APPLIES_TO[$c->applies_to] ?? $c->applies_to,
            'targets'         => match ($c->applies_to) {
                'categories' => $c->categories->pluck('name')->values(),
                'brands'     => $c->brands->pluck('name')->values(),
                'products'   => $c->products->pluck('name')->values(),
                default      => [],
            },
            'validity'        => $c->validity_label,
            'total_discount'  => \App\Models\SiteSetting::current()->formatMoney($c->total_discount ?? 0),
            'recent_uses'     => ($recentUses[$c->id] ?? collect())->map(fn ($u) => [
                'user'   => $u['user'],
                'amount' => \App\Models\SiteSetting::current()->formatMoney($u['amount']),
                'date'   => $u['date'],
            ])->values(),
            'created_at'      => $c->created_at?->format('d M Y, h:i A'),
            'updated_at'      => $c->updated_at?->format('d M Y, h:i A'),
            'edit_url'        => route('admin.coupons.edit', $c),
        ])->values();

        $counts = collect(Coupon::STATES)->mapWithKeys(fn ($s) => [$s => Coupon::state($s)->count()]);

        return view('admin.coupons.index', [
            'coupons'     => $coupons,
            'details'     => $details,
            'counts'      => $counts,
            'total'       => Coupon::count(),
            'stateLabels' => self::STATE_LABELS,
            'q'           => $q,
            'state'       => $state,
        ]);
    }

    /* ---------------------------------------------------------------
     |  CREATE / STORE
     * --------------------------------------------------------------*/
    public function create(): View
    {
        return view('admin.coupons.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeCode($request);
        $data = $request->validate($this->rules($request));

        $coupon = DB::transaction(function () use ($request, $data) {
            $coupon = Coupon::create($this->payload($request, $data));
            $this->syncTargets($coupon, $data);

            return $coupon;
        });

        return redirect()->route('admin.coupons.index')->with('success', "Coupon {$coupon->code} created.");
    }

    /* ---------------------------------------------------------------
     |  EDIT / UPDATE
     * --------------------------------------------------------------*/
    public function edit(Coupon $coupon): View
    {
        $coupon->load(['categories:id', 'brands:id', 'products:id'])
            ->loadSum('usages as total_discount', 'discount_amount');

        return view('admin.coupons.edit', $this->formData() + ['coupon' => $coupon]);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->normalizeCode($request);
        $data = $request->validate($this->rules($request, $coupon));

        DB::transaction(function () use ($request, $data, $coupon) {
            $coupon->update($this->payload($request, $data));
            $this->syncTargets($coupon, $data);
        });

        return redirect()->route('admin.coupons.index')->with('success', "Coupon {$coupon->code} updated.");
    }

    /* ---------------------------------------------------------------
     |  DELETE / DUPLICATE / TOGGLE
     * --------------------------------------------------------------*/
    public function destroy(Coupon $coupon): RedirectResponse
    {
        // Used coupons are part of order history, so they are switched off instead
        if ($coupon->used_count > 0 || $coupon->usages()->exists()) {
            return back()->with('error', "{$coupon->code} has been used {$coupon->used_count} " . Str::plural('time', $coupon->used_count) . '. Turn it off instead of deleting it.');
        }

        $coupon->delete();

        return back()->with('success', 'Coupon deleted successfully.');
    }

    /** POST /admin/coupons/{coupon}/duplicate : copy with a new code, switched off */
    public function duplicate(Coupon $coupon): RedirectResponse
    {
        $copy = DB::transaction(function () use ($coupon) {
            $copy = $coupon->replicate(['used_count']);
            $copy->code       = $this->copyCode($coupon->code);
            $copy->used_count = 0;
            $copy->is_active  = false;
            $copy->save();

            $copy->categories()->sync($coupon->categories()->pluck('categories.id'));
            $copy->brands()->sync($coupon->brands()->pluck('brands.id'));
            $copy->products()->sync($coupon->products()->pluck('products.id'));

            return $copy;
        });

        return redirect()->route('admin.coupons.edit', $copy)
            ->with('success', "Copied as {$copy->code}. It is switched off until you turn it on.");
    }

    /** PATCH /admin/coupons/{coupon}/toggle */
    public function toggle(Coupon $coupon): JsonResponse
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return response()->json([
            'success'     => true,
            'value'       => $coupon->is_active,
            'state'       => $coupon->state,
            'state_label' => self::STATE_LABELS[$coupon->state],
            'message'     => $coupon->is_active ? 'Coupon switched on.' : 'Coupon switched off.',
        ]);
    }

    /* ---------------------------------------------------------------
     |  HELPERS
     * --------------------------------------------------------------*/
    private function formData(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands'     => Brand::orderBy('name')->get(['id', 'name']),
            'products'   => Product::orderBy('name')->limit(500)->get(['id', 'name', 'sku']),
        ];
    }

    private function rules(Request $request, ?Coupon $coupon = null): array
    {
        $type      = $request->input('type');
        $appliesTo = $request->input('applies_to', 'all');

        $valueRules = match ($type) {
            'free_shipping' => ['nullable'],
            'percentage'    => ['required', 'numeric', 'min:0.01', 'max:100'],
            default         => ['required', 'numeric', 'min:0.01'],
        };

        $rules = [
            'code'        => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('coupons', 'code')->ignore($coupon?->id)],
            'name'        => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],

            'type'                => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'value'               => $valueRules,
            'max_discount_amount' => ['nullable', 'numeric', 'min:0.01'],
            'min_order_amount'    => ['nullable', 'numeric', 'min:0'],

            'usage_limit'          => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],

            'applies_to' => ['required', Rule::in(array_keys(Coupon::APPLIES_TO))],

            'starts_at'  => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', $request->filled('starts_at') ? 'after:starts_at' : 'date'],
        ];

        $targets = ['categories' => 'categories', 'brands' => 'brands', 'products' => 'products'];

        foreach ($targets as $field => $table) {
            if ($appliesTo === $field) {
                $rules[$field]        = ['required', 'array', 'min:1'];
                $rules["{$field}.*"]  = ['integer', Rule::exists($table, 'id')];
            }
        }

        return $rules;
    }

    /** Codes are stored in capitals without spaces. */
    private function normalizeCode(Request $request): void
    {
        $request->merge([
            'code' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('code'))),
        ]);
    }

    private function payload(Request $request, array $data): array
    {
        $type = $data['type'];

        return [
            'code'                 => $data['code'],
            'name'                 => $data['name'] ?? null,
            'description'          => $data['description'] ?? null,
            'type'                 => $type,
            'value'                => $type === 'free_shipping' ? 0 : $data['value'],
            'max_discount_amount'  => $type === 'percentage' ? ($data['max_discount_amount'] ?? null) : null,
            'min_order_amount'     => $data['min_order_amount'] ?? null,
            'usage_limit'          => $data['usage_limit'] ?? null,
            'usage_limit_per_user' => $data['usage_limit_per_user'] ?? null,
            'first_order_only'     => $request->boolean('first_order_only'),
            'applies_to'           => $data['applies_to'],
            'exclude_discounted'   => $request->boolean('exclude_discounted'),
            'starts_at'            => $data['starts_at'] ?? null,
            'expires_at'           => $data['expires_at'] ?? null,
            'is_active'            => $request->boolean('is_active'),
        ];
    }

    /** Keep only the targets that match "applies to", clear the rest. */
    private function syncTargets(Coupon $coupon, array $data): void
    {
        $appliesTo = $data['applies_to'];

        $coupon->categories()->sync($appliesTo === 'categories' ? $data['categories'] : []);
        $coupon->brands()->sync($appliesTo === 'brands' ? $data['brands'] : []);
        $coupon->products()->sync($appliesTo === 'products' ? $data['products'] : []);
    }

    /** SAVE10 -> SAVE10-COPY -> SAVE10-COPY2 ... */
    private function copyCode(string $code): string
    {
        $base      = Str::limit(preg_replace('/-COPY\d*$/', '', $code), 44, '');
        $candidate = "{$base}-COPY";
        $i         = 2;

        while (Coupon::where('code', $candidate)->exists()) {
            $candidate = "{$base}-COPY{$i}";
            $i++;
        }

        return $candidate;
    }
}
