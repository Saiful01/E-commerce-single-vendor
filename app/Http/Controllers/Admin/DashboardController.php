<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Models\Wishlist;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardController extends Controller
{
    private const PALETTE = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#0ea5e9', '#8b5cf6', '#ec4899', '#14b8a6', '#64748b'];

    private const LOW_STOCK = 5;

    public function index(Request $request)
    {
        [$from, $to, $period] = $this->resolveRange($request);

        $statusId = $request->integer('status') ?: null;
        $payment  = array_key_exists((string) $request->input('payment'), Order::PAYMENT_STATUSES) ? $request->input('payment') : null;

        // Previous period (same length) - growth % er jonno
        $days     = (int) $from->diffInDays($to) + 1;
        $prevTo   = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        // Filter shoho base query
        $filtered = fn (Carbon $f, Carbon $t) => Order::query()
            ->whereBetween('created_at', [$f, $t])
            ->when($statusId, fn ($q) => $q->where('order_status_id', $statusId))
            ->when($payment, fn ($q) => $q->where('payment_status', $payment));

        // Status filter chara (status chart-e shob status dekhate)
        $noStatus = fn (Carbon $f, Carbon $t) => Order::query()
            ->whereBetween('created_at', [$f, $t])
            ->when($payment, fn ($q) => $q->where('payment_status', $payment));

        /* ---------------------------- KPI ---------------------------- */
        $current  = $this->kpis($filtered($from, $to), $from, $to);
        $previous = $this->kpis($filtered($prevFrom, $prevTo), $prevFrom, $prevTo);

        $kpi = [];
        foreach (['revenue', 'orders', 'aov', 'collected', 'due', 'customers'] as $key) {
            $kpi[$key] = [
                'value'  => $current[$key],
                'growth' => $this->growth($current[$key], $previous[$key]),
            ];
        }

        $cancelled = (clone $filtered($from, $to))->whereNotNull('cancelled_at')->count();
        $totalIncCancelled = $current['orders'] + $cancelled;
        $cancelRate = $totalIncCancelled > 0 ? round($cancelled / $totalIncCancelled * 100, 1) : 0;

        /* ------------------------- Sales chart ------------------------- */
        $sales = $this->salesSeries($filtered($from, $to), $from, $to, $days);

        /* ------------------------ Status chart ------------------------ */
        $statusRows = $noStatus($from, $to)
            ->select('order_status_id', DB::raw('COUNT(*) as c'))
            ->groupBy('order_status_id')
            ->with('status')
            ->get();

        $statusChart = ['labels' => [], 'data' => [], 'colors' => []];
        foreach ($statusRows as $i => $row) {
            $color = $row->status->color ?? null;
            $statusChart['labels'][] = $row->status->name ?? 'Unknown';
            $statusChart['data'][]   = (int) $row->c;
            $statusChart['colors'][] = ($color && str_starts_with($color, '#')) ? $color : self::PALETTE[$i % count(self::PALETTE)];
        }

        /* ----------------------- Payment status ----------------------- */
        $paymentRows = $filtered($from, $to)->whereNull('cancelled_at')
            ->select('payment_status', DB::raw('COUNT(*) as c'))
            ->groupBy('payment_status')->get();

        $paymentChart = [
            'labels' => $paymentRows->map(fn ($r) => Order::PAYMENT_STATUSES[$r->payment_status] ?? $r->payment_status)->all(),
            'data'   => $paymentRows->pluck('c')->map(fn ($v) => (int) $v)->all(),
            'colors' => ['#ef4444', '#f59e0b', '#10b981', '#64748b'],
        ];

        /* ------------------------ Delivery zone ------------------------ */
        $zoneRows = $filtered($from, $to)->whereNull('cancelled_at')
            ->select('delivery_zone', DB::raw('COUNT(*) as c'))
            ->groupBy('delivery_zone')->get();

        $zoneChart = [
            'labels' => $zoneRows->map(fn ($r) => Order::ZONES[$r->delivery_zone] ?? ($r->delivery_zone ?: 'N/A'))->all(),
            'data'   => $zoneRows->pluck('c')->map(fn ($v) => (int) $v)->all(),
            'colors' => ['#6366f1', '#0ea5e9', '#94a3b8'],
        ];

        /* ----------------------- Payment gateways ----------------------- */
        $gatewayRows = $filtered($from, $to)->whereNull('cancelled_at')
            ->select('payment_gateway_id', DB::raw('COUNT(*) as c'), DB::raw('COALESCE(SUM(total),0) as r'))
            ->groupBy('payment_gateway_id')
            ->with('paymentGateway')
            ->orderByDesc('r')
            ->get();

        $gatewayChart = [
            'labels'  => $gatewayRows->map(fn ($r) => $r->paymentGateway->name ?? 'N/A')->all(),
            'revenue' => $gatewayRows->map(fn ($r) => (float) $r->r)->all(),
            'orders'  => $gatewayRows->map(fn ($r) => (int) $r->c)->all(),
        ];

        /* -------------------- Top products / low stock -------------------- */
        $topProducts = $this->topProducts($from, $to, $statusId, $payment);

        $lowStock = Product::where('is_active', true)
            ->where('stock_quantity', '<=', self::LOW_STOCK)
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        $inventory = [
            'active'       => Product::where('is_active', true)->count(),
            'low_stock'    => Product::where('is_active', true)->where('stock_quantity', '>', 0)->where('stock_quantity', '<=', self::LOW_STOCK)->count(),
            'out_of_stock' => Product::where('is_active', true)->where('stock_quantity', '<=', 0)->count(),
        ];

        /* ------------------------- Latest orders ------------------------- */
        $latestOrders = Order::with('status')->latest()->limit(8)->get();

        /* --------------------- Attention / engagement --------------------- */
        $attention = [
            'pending_reviews' => Schema::hasTable('product_reviews') ? ProductReview::where('status', 'pending')->count() : 0,
            'open_carts'      => Schema::hasTable('abandoned_carts') ? AbandonedCart::open()->count() : 0,
            'open_cart_value' => Schema::hasTable('abandoned_carts') ? (float) AbandonedCart::open()->sum('total') : 0,
            'wishlists'       => Schema::hasTable('wishlists') ? Wishlist::count() : 0,
            'unpaid_orders'   => Order::whereNull('cancelled_at')->whereIn('payment_status', ['unpaid', 'partial'])->count(),
            'total_customers' => User::where('user_type', 'customer')->count(),
        ];

        $charts = [
            'sales'   => $sales,
            'status'  => $statusChart,
            'payment' => $paymentChart,
            'zone'    => $zoneChart,
            'gateway' => $gatewayChart,
        ];

        return view('admin.dashboard', [
            'currency'     => '৳',
            'period'       => $period,
            'from'         => $from,
            'to'           => $to,
            'prevFrom'     => $prevFrom,
            'prevTo'       => $prevTo,
            'statusId'     => $statusId,
            'payment'      => $payment,
            'statuses'     => OrderStatus::query()->get(),
            'kpi'          => $kpi,
            'cancelled'    => $cancelled,
            'cancelRate'   => $cancelRate,
            'charts'       => $charts,
            'topProducts'  => $topProducts,
            'lowStock'     => $lowStock,
            'inventory'    => $inventory,
            'latestOrders' => $latestOrders,
            'attention'    => $attention,
            'lowStockLimit'=> self::LOW_STOCK,
        ]);
    }

    /* ====================================================================== */

    /** @return array{0:Carbon,1:Carbon,2:string} */
    private function resolveRange(Request $request): array
    {
        $period = $request->input('period', '30d');
        $now = now();

        switch ($period) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today'];
            case '7d':
                return [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), '7d'];
            case 'this_month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'this_month'];
            case 'last_month':
                $lm = $now->copy()->subMonthNoOverflow();
                return [$lm->copy()->startOfMonth(), $lm->copy()->endOfMonth(), 'last_month'];
            case 'this_year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfDay(), 'this_year'];
            case 'custom':
                try {
                    $f = Carbon::parse($request->input('from'))->startOfDay();
                    $t = Carbon::parse($request->input('to'))->endOfDay();
                    if ($f->gt($t)) {
                        [$f, $t] = [$t->copy()->startOfDay(), $f->copy()->endOfDay()];
                    }
                    return [$f, $t, 'custom'];
                } catch (Throwable) {
                    // fallthrough to default
                }
        }

        return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), '30d'];
    }

    private function kpis($query, Carbon $from, Carbon $to): array
    {
        $valid = (clone $query)->whereNull('cancelled_at');

        $orders    = (clone $valid)->count();
        $revenue   = (float) (clone $valid)->sum('total');
        $collected = (float) (clone $valid)->sum('paid_amount');

        return [
            'orders'    => $orders,
            'revenue'   => $revenue,
            'aov'       => $orders > 0 ? round($revenue / $orders, 2) : 0.0,
            'collected' => $collected,
            'due'       => max($revenue - $collected, 0),
            'customers' => User::where('user_type', 'customer')->whereBetween('created_at', [$from, $to])->count(),
        ];
    }

    private function growth(float|int $current, float|int $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    private function salesSeries($query, Carbon $from, Carbon $to, int $days): array
    {
        $daily = (clone $query)->whereNull('cancelled_at')
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c, COALESCE(SUM(total),0) as r')
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($row) => (string) $row->d);

        $labels = $revenue = $orders = [];

        if ($days <= 92) {
            foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
                $row = $daily->get($day->toDateString());
                $labels[]  = $day->format('d M');
                $revenue[] = (float) ($row->r ?? 0);
                $orders[]  = (int) ($row->c ?? 0);
            }
            $granularity = 'Daily';
        } else {
            // Beshi din hole mashik group
            $monthly = [];
            foreach ($daily as $date => $row) {
                $key = substr($date, 0, 7);
                $monthly[$key]['r'] = ($monthly[$key]['r'] ?? 0) + (float) $row->r;
                $monthly[$key]['c'] = ($monthly[$key]['c'] ?? 0) + (int) $row->c;
            }
            foreach (CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to) as $month) {
                $key = $month->format('Y-m');
                $labels[]  = $month->format('M Y');
                $revenue[] = $monthly[$key]['r'] ?? 0;
                $orders[]  = $monthly[$key]['c'] ?? 0;
            }
            $granularity = 'Monthly';
        }

        return compact('labels', 'revenue', 'orders', 'granularity');
    }

    /**
     * order_items-er column name auto-detect kore (quantity/qty, total/subtotal/price).
     */
    private function topProducts(Carbon $from, Carbon $to, ?int $statusId, ?string $payment)
    {
        try {
            $qty = Schema::hasColumn('order_items', 'quantity') ? 'quantity' : (Schema::hasColumn('order_items', 'qty') ? 'qty' : null);

            if (! $qty || ! Schema::hasColumn('order_items', 'product_id')) {
                return collect();
            }

            $line = match (true) {
                Schema::hasColumn('order_items', 'total')    => 'order_items.total',
                Schema::hasColumn('order_items', 'subtotal') => 'order_items.subtotal',
                Schema::hasColumn('order_items', 'price')    => "order_items.price * order_items.$qty",
                default                                      => '0',
            };

            $rows = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereBetween('orders.created_at', [$from, $to])
                ->whereNull('orders.cancelled_at')
                ->when($statusId, fn ($q) => $q->where('orders.order_status_id', $statusId))
                ->when($payment, fn ($q) => $q->where('orders.payment_status', $payment))
                ->whereNotNull('order_items.product_id')
                ->selectRaw("order_items.product_id, SUM(order_items.$qty) as qty, SUM($line) as revenue")
                ->groupBy('order_items.product_id')
                ->orderByDesc('qty')
                ->limit(5)
                ->get();

            $products = Product::whereIn('id', $rows->pluck('product_id'))->get()->keyBy('id');

            return $rows->map(function ($row) use ($products) {
                $row->product = $products->get($row->product_id);
                return $row;
            });
        } catch (Throwable) {
            return collect();
        }
    }
}
