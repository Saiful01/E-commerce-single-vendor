@extends('layouts.admin')

@section('title', 'Dashboard')

@php
    $periods = [
        'today'      => 'Today',
        '7d'         => 'Last 7 days',
        '30d'        => 'Last 30 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year'  => 'This year',
        'custom'     => 'Custom range',
    ];

    $money = fn ($v, $d = 0) => $currency . number_format($v, $d);

    $cards = [
        ['key' => 'revenue',   'label' => 'Total Revenue',     'format' => 'money', 'tone' => 'indigo',
         'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['key' => 'orders',    'label' => 'Orders',            'format' => 'int',   'tone' => 'sky',
         'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
        ['key' => 'aov',       'label' => 'Avg. Order Value',  'format' => 'money', 'tone' => 'violet',
         'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
        ['key' => 'collected', 'label' => 'Payment Collected', 'format' => 'money', 'tone' => 'emerald',
         'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
        ['key' => 'due',       'label' => 'Due Amount',        'format' => 'money', 'tone' => 'amber', 'invert' => true,
         'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['key' => 'customers', 'label' => 'New Customers',     'format' => 'int',   'tone' => 'pink',
         'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    ];

    $tones = [
        'indigo'  => 'bg-indigo-50 text-indigo-600',
        'sky'     => 'bg-sky-50 text-sky-600',
        'violet'  => 'bg-violet-50 text-violet-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber'   => 'bg-amber-50 text-amber-600',
        'pink'    => 'bg-pink-50 text-pink-600',
    ];

    $paymentBadge = [
        'unpaid'   => 'bg-red-100 text-red-700',
        'partial'  => 'bg-amber-100 text-amber-700',
        'paid'     => 'bg-green-100 text-green-700',
        'refunded' => 'bg-slate-100 text-slate-600',
    ];

    $hasFilter = $period !== '30d' || $statusId || $payment;
@endphp

@section('content')

    {{-- ===================== HEADER + FILTERS ===================== --}}
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Welcome back, {{ Str::before(Auth::user()->name, ' ') }} 👋</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
                <span class="text-gray-400">· compared with {{ $prevFrom->format('d M') }} – {{ $prevTo->format('d M Y') }}</span>
            </p>
        </div>

        <form method="GET" action="{{ route('admin.dashboard') }}"
              x-data="{ period: '{{ $period }}' }"
              class="flex flex-wrap items-end gap-2 bg-white border border-gray-200 rounded-2xl shadow-sm p-2.5">

            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-0.5 px-1">Period</label>
                <select name="period" x-model="period" @change="if (period !== 'custom') $el.form.submit()"
                        class="rounded-xl border-gray-300 text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($periods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div x-show="period === 'custom'" x-cloak class="flex items-end gap-2">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-0.5 px-1">From</label>
                    <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-xl border-gray-300 text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-0.5 px-1">To</label>
                    <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-xl border-gray-300 text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-0.5 px-1">Order status</label>
                <select name="status" onchange="this.form.submit()" class="rounded-xl border-gray-300 text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All statuses</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->id }}" @selected($statusId == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-0.5 px-1">Payment</label>
                <select name="payment" onchange="this.form.submit()" class="rounded-xl border-gray-300 text-sm py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All payments</option>
                    @foreach(\App\Models\Order::PAYMENT_STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected($payment === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button x-show="period === 'custom'" x-cloak class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl">Apply</button>

            @if($hasFilter)
                <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800">Reset</a>
            @endif
        </form>
    </div>

    {{-- ===================== KPI CARDS ===================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        @foreach($cards as $card)
            @php
                $data   = $kpi[$card['key']];
                $value  = $card['format'] === 'money' ? $money($data['value']) : number_format($data['value']);
                $growth = $data['growth'];
                $good   = ! empty($card['invert']) ? $growth <= 0 : $growth >= 0;
            @endphp
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1.5">{{ $value }}</p>
                    </div>
                    <span class="w-11 h-11 rounded-xl flex items-center justify-center {{ $tones[$card['tone']] }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"></path></svg>
                    </span>
                </div>
                <div class="flex items-center gap-2 mt-3 text-xs">
                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md font-semibold {{ $growth == 0 ? 'bg-gray-100 text-gray-600' : ($good ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                        @if($growth > 0) ▲ @elseif($growth < 0) ▼ @else – @endif
                        {{ abs($growth) }}%
                    </span>
                    <span class="text-gray-400">vs previous period</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===================== SALES CHART + STATUS ===================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800">Sales overview</h3>
                    <p class="text-xs text-gray-400">{{ $charts['sales']['granularity'] }} revenue &amp; orders (cancelled excluded)</p>
                </div>
                <div class="hidden sm:flex gap-4 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></span>Revenue</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Orders</span>
                </div>
            </div>
            <div class="relative h-80"><canvas id="salesChart"></canvas></div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">Orders by status</h3>
            <p class="text-xs text-gray-400 mb-4">Selected period</p>
            @if(array_sum($charts['status']['data']) > 0)
                <div class="relative h-56"><canvas id="statusChart"></canvas></div>
                <ul class="mt-4 space-y-1.5 text-sm">
                    @foreach($charts['status']['labels'] as $i => $label)
                        <li class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-2 text-gray-600"><span class="w-2.5 h-2.5 rounded-full" style="background: {{ $charts['status']['colors'][$i] }}"></span>{{ $label }}</span>
                            <span class="font-semibold text-gray-800">{{ $charts['status']['data'][$i] }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-gray-400 py-16 text-center">No orders in this period.</p>
            @endif
        </div>
    </div>

    {{-- ===================== BREAKDOWNS ===================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-4">Payment status</h3>
            @if(array_sum($charts['payment']['data']) > 0)
                <div class="relative h-52"><canvas id="paymentChart"></canvas></div>
            @else
                <p class="text-sm text-gray-400 py-16 text-center">No data.</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-4">Delivery zone</h3>
            @if(array_sum($charts['zone']['data']) > 0)
                <div class="relative h-52"><canvas id="zoneChart"></canvas></div>
            @else
                <p class="text-sm text-gray-400 py-16 text-center">No data.</p>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 md:col-span-2 xl:col-span-1">
            <h3 class="font-semibold text-gray-800 mb-4">Revenue by payment method</h3>
            @if(count($charts['gateway']['labels']))
                <div class="relative h-52"><canvas id="gatewayChart"></canvas></div>
            @else
                <p class="text-sm text-gray-400 py-16 text-center">No data.</p>
            @endif
        </div>
    </div>

    {{-- ===================== LATEST ORDERS + TOP PRODUCTS ===================== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div>
                    <h3 class="font-semibold text-gray-800">Latest orders</h3>
                    <p class="text-xs text-gray-400">Most recent, filter-er baire</p>
                </div>
                @can('manage-orders')
                    <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">View all &rarr;</a>
                @endcan
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">Order</th>
                        <th class="px-5 py-3 text-left">Customer</th>
                        <th class="px-5 py-3 text-left">Total</th>
                        <th class="px-5 py-3 text-left">Payment</th>
                        <th class="px-5 py-3 text-left">Status</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($latestOrders as $o)
                        @php
                            $color = $o->status->color ?? null;
                            $isHex = $color && str_starts_with($color, '#');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.orders.show', $o) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">{{ $o->order_number }}</a>
                                <p class="text-xs text-gray-400">{{ $o->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-gray-800">{{ $o->customer_name }}</p>
                                <p class="text-xs text-gray-400">{{ $o->customer_phone }}</p>
                            </td>
                            <td class="px-5 py-3 font-semibold text-gray-800">{{ $money($o->total, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $paymentBadge[$o->payment_status] ?? 'bg-gray-100 text-gray-600' }}">{{ $o->payment_status_label }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $isHex ? '' : 'bg-gray-100 text-gray-700' }}"
                                      @if($isHex) style="background: {{ $color }}22; color: {{ $color }}" @endif>
                                    {{ $o->status->name ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No orders yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800">Top selling products</h3>
            <p class="text-xs text-gray-400 mb-4">By units sold in selected period</p>
            <div class="space-y-4">
                @forelse($topProducts as $i => $row)
                    <div class="flex items-center gap-3">
                        <span class="w-6 text-sm font-bold text-gray-300">#{{ $i + 1 }}</span>
                        @if($row->product)
                            <img src="{{ $row->product->featured_image_url }}" alt="" class="w-11 h-11 rounded-xl object-cover bg-gray-100 flex-shrink-0">
                        @else
                            <div class="w-11 h-11 rounded-xl bg-gray-100 flex-shrink-0"></div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $row->product->name ?? 'Deleted product' }}</p>
                            <p class="text-xs text-gray-400">{{ number_format($row->qty) }} sold</p>
                        </div>
                        <p class="text-sm font-semibold text-gray-700">{{ $money($row->revenue) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 py-10 text-center">No sales data in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===================== INVENTORY + ATTENTION ===================== --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800">Inventory alerts</h3>
                    <p class="text-xs text-gray-400">Stock {{ $lowStockLimit }} ba tar kom hole low stock dhora hoy</p>
                </div>
                <div class="flex gap-2 text-xs font-semibold">
                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">{{ $inventory['active'] }} active</span>
                    <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">{{ $inventory['low_stock'] }} low</span>
                    <span class="px-2.5 py-1 rounded-full bg-red-100 text-red-700">{{ $inventory['out_of_stock'] }} out</span>
                </div>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse($lowStock as $p)
                    <div class="flex items-center gap-3 py-3">
                        <img src="{{ $p->featured_image_url }}" alt="" class="w-10 h-10 rounded-lg object-cover bg-gray-100">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $p->name }}</p>
                            <p class="text-xs text-gray-400">SKU: {{ $p->sku ?? '—' }}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $p->stock_quantity <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $p->stock_quantity <= 0 ? 'Out of stock' : $p->stock_quantity . ' left' }}
                        </span>
                        @can('manage-products')
                            <a href="{{ route('admin.products.edit', $p) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Restock</a>
                        @endcan
                    </div>
                @empty
                    <p class="text-sm text-gray-400 py-8 text-center">All products are well stocked 🎉</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-4">Needs attention</h3>
            @php
                $items = [
                    ['label' => 'Unpaid / partial orders', 'value' => $attention['unpaid_orders'], 'tone' => 'bg-red-50 text-red-600',    'can' => 'manage-orders',            'route' => 'admin.orders.index'],
                    ['label' => 'Reviews awaiting approval','value' => $attention['pending_reviews'], 'tone' => 'bg-amber-50 text-amber-600', 'can' => 'manage-product-reviews', 'route' => 'admin.product-reviews.index', 'params' => ['status' => 'pending']],
                    ['label' => 'Open abandoned carts ('.$money($attention['open_cart_value']).')', 'value' => $attention['open_carts'], 'tone' => 'bg-indigo-50 text-indigo-600', 'can' => 'manage-abandoned-carts', 'route' => 'admin.abandoned-carts.index'],
                    ['label' => 'Wishlisted items',        'value' => $attention['wishlists'],       'tone' => 'bg-pink-50 text-pink-600',     'can' => 'manage-wishlists',        'route' => 'admin.wishlists.index'],
                    ['label' => 'Total customers',         'value' => $attention['total_customers'], 'tone' => 'bg-sky-50 text-sky-600',       'can' => 'manage-users',            'route' => 'admin.users.index'],
                ];
            @endphp
            <div class="space-y-2.5">
                @foreach($items as $it)
                    @can($it['can'])
                        <a href="{{ route($it['route'], $it['params'] ?? []) }}" class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/30 transition">
                            <span class="text-sm text-gray-600">{{ $it['label'] }}</span>
                            <span class="min-w-[2.25rem] text-center px-2 py-1 rounded-lg text-sm font-bold {{ $it['tone'] }}">{{ number_format($it['value']) }}</span>
                        </a>
                    @endcan
                @endforeach

                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                    <span class="text-sm text-gray-600">Cancelled orders ({{ $cancelRate }}%)</span>
                    <span class="min-w-[2.25rem] text-center px-2 py-1 rounded-lg text-sm font-bold bg-slate-100 text-slate-600">{{ number_format($cancelled) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== CHARTS JS ===================== --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const charts   = @json($charts);
            const currency = @json($currency);
            const nf       = new Intl.NumberFormat('en-US');
            const font     = { family: 'Figtree, ui-sans-serif, system-ui', size: 12 };

            Chart.defaults.font = font;
            Chart.defaults.color = '#64748b';

            const el = (id) => document.getElementById(id);

            // ---- Sales (bar + line) ----
            if (el('salesChart')) {
                new Chart(el('salesChart'), {
                    data: {
                        labels: charts.sales.labels,
                        datasets: [
                            { type: 'bar',  label: 'Revenue', data: charts.sales.revenue, backgroundColor: 'rgba(99,102,241,.85)', borderRadius: 6, maxBarThickness: 28, yAxisID: 'y', order: 2 },
                            { type: 'line', label: 'Orders',  data: charts.sales.orders,  borderColor: '#10b981', backgroundColor: '#10b981', tension: .35, pointRadius: 2.5, borderWidth: 2, yAxisID: 'y1', order: 1 },
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (c) => c.dataset.label === 'Revenue' ? ' Revenue: ' + currency + nf.format(c.parsed.y) : ' Orders: ' + c.parsed.y } }
                        },
                        scales: {
                            x:  { grid: { display: false }, ticks: { maxTicksLimit: 12 } },
                            y:  { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: (v) => currency + nf.format(v) } },
                            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { precision: 0 } }
                        }
                    }
                });
            }

            // ---- Doughnut helper ----
            const doughnut = (id, cfg, legend) => {
                if (!el(id)) return;
                new Chart(el(id), {
                    type: 'doughnut',
                    data: { labels: cfg.labels, datasets: [{ data: cfg.data, backgroundColor: cfg.colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }] },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '68%',
                        plugins: { legend: { display: legend, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 14 } } }
                    }
                });
            };

            doughnut('statusChart',  charts.status,  false);
            doughnut('paymentChart', charts.payment, true);
            doughnut('zoneChart',    charts.zone,    true);

            // ---- Gateway (horizontal bar) ----
            if (el('gatewayChart')) {
                new Chart(el('gatewayChart'), {
                    type: 'bar',
                    data: { labels: charts.gateway.labels, datasets: [{ data: charts.gateway.revenue, backgroundColor: '#6366f1', borderRadius: 6, maxBarThickness: 22 }] },
                    options: {
                        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (c) => ' ' + currency + nf.format(c.parsed.x) + ' · ' + charts.gateway.orders[c.dataIndex] + ' orders' } }
                        },
                        scales: {
                            x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: (v) => currency + nf.format(v) } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }
        })();
    </script>
@endsection
