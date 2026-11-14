<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->order_number }}</title>
    @if ($settings->favicon_url)
        <link rel="icon" href="{{ $settings->favicon_url }}">
    @endif
    <style>
        :root { --ink: #1e293b; --muted: #64748b; --line: #e2e8f0; --brand: {{ $settings->primary_color }}; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: var(--ink); font: 14px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .toolbar { max-width: 820px; margin: 20px auto 0; padding: 0 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .toolbar a, .toolbar button { font: inherit; font-size: 13px; font-weight: 600; padding: 9px 16px; border-radius: 10px; border: 1px solid var(--line); background: #fff; color: var(--ink); cursor: pointer; text-decoration: none; }
        .toolbar button { background: var(--brand); border-color: var(--brand); color: #fff; }
        .sheet { max-width: 820px; margin: 16px auto 40px; background: #fff; padding: 44px; border-radius: 14px; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
        header { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 24px; border-bottom: 3px solid var(--brand); }
        .logo { max-height: 52px; max-width: 220px; object-fit: contain; }
        .shop-name { font-size: 22px; font-weight: 800; color: var(--brand); margin: 0; }
        .small { font-size: 12px; color: var(--muted); }
        h1 { margin: 0; font-size: 26px; letter-spacing: .06em; text-transform: uppercase; text-align: right; }
        .meta { text-align: right; margin-top: 6px; }
        .meta strong { color: var(--ink); }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin: 26px 0; }
        h3 { margin: 0 0 6px; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); padding: 10px 8px; background: #f8fafc; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
        tbody td { padding: 12px 8px; border-bottom: 1px solid var(--line); vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .totals { width: 320px; margin: 20px 0 0 auto; }
        .totals div { display: flex; justify-content: space-between; padding: 5px 0; }
        .totals .grand { margin-top: 6px; padding-top: 10px; border-top: 2px solid var(--ink); font-size: 17px; font-weight: 800; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; border: 1px solid var(--line); }
        .paid { color: #047857; background: #ecfdf5; border-color: #a7f3d0; }
        .unpaid { color: #be123c; background: #fff1f2; border-color: #fecdd3; }
        footer { margin-top: 36px; padding-top: 16px; border-top: 1px solid var(--line); text-align: center; }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; padding: 0; box-shadow: none; border-radius: 0; max-width: none; }
            @page { margin: 14mm; }
        }
        @media (max-width: 600px) {
            .sheet { padding: 22px; }
            header, .cols { grid-template-columns: 1fr; display: block; }
            h1, .meta { text-align: left; margin-top: 14px; }
            .cols > div + div { margin-top: 18px; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <a href="{{ route('admin.orders.show', $order) }}">&larr; Back to order</a>
    <button type="button" onclick="window.print()">Print invoice</button>
</div>

<main class="sheet">
    <header>
        <div>
            @if ($settings->logo_url)
                <img class="logo" src="{{ $settings->logo_url }}" alt="{{ $settings->site_name }}">
            @else
                <p class="shop-name">{{ $settings->site_name }}</p>
            @endif
            <p class="small" style="margin: 8px 0 0">
                @if ($settings->address) {{ $settings->address }}<br> @endif
                @if ($settings->phone_number) {{ $settings->phone_number }} @endif
                @if ($settings->phone_number && $settings->primary_email) · @endif
                @if ($settings->primary_email) {{ $settings->primary_email }} @endif
            </p>
        </div>
        <div>
            <h1>Invoice</h1>
            <p class="small meta">
                <strong>{{ $order->order_number }}</strong><br>
                {{ $order->created_at->format('d M Y') }}
            </p>
        </div>
    </header>

    <section class="cols">
        <div>
            <h3>Bill to</h3>
            <strong>{{ $order->customer_name }}</strong><br>
            <span class="small">{{ $order->customer_phone }}@if ($order->customer_email)<br>{{ $order->customer_email }}@endif</span>
        </div>
        <div>
            <h3>Deliver to</h3>
            <strong>{{ $order->shipping_name }}</strong><br>
            <span class="small">
                    {{ $order->shipping_phone }}<br>
                    {{ $order->shipping_address }}<br>
                    {{ $order->shipping_area }}, {{ $order->shipping_city }}
                </span>
        </div>
    </section>

    <table>
        <thead>
        <tr>
            <th>Item</th>
            <th class="num">Price</th>
            <th class="num">Qty</th>
            <th class="num">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td>
                    <strong>{{ $item->name }}</strong>
                    @if ($item->variant_label) <br><span class="small">{{ $item->variant_label }}</span> @endif
                    @if ($item->sku) <br><span class="small">SKU: {{ $item->sku }}</span> @endif
                </td>
                <td class="num">{{ $settings->formatMoney($item->unit_price) }}</td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ $settings->formatMoney($item->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal</span><span>{{ $settings->formatMoney($order->subtotal) }}</span></div>
        @if ($order->discount_amount > 0)
            <div><span>Discount @if ($order->coupon_code) ({{ $order->coupon_code }}) @endif</span><span>- {{ $settings->formatMoney($order->discount_amount) }}</span></div>
        @endif
        <div><span>Delivery</span><span>{{ $settings->formatMoney($order->shipping_charge) }}</span></div>
        @if ($order->payment_fee > 0)
            <div><span>Payment fee</span><span>{{ $settings->formatMoney($order->payment_fee) }}</span></div>
        @endif
        <div class="grand"><span>Total</span><span>{{ $settings->formatMoney($order->total) }}</span></div>
        <div><span>Paid</span><span>{{ $settings->formatMoney($order->paid_amount) }}</span></div>
        @if ($order->due_amount > 0 && $order->payment_status !== 'refunded')
            <div><strong>Amount due</strong><strong>{{ $settings->formatMoney($order->due_amount) }}</strong></div>
        @endif
    </div>

    <p class="small" style="margin-top: 22px">
        Payment method: <strong style="color: var(--ink)">{{ $order->paymentGateway?->name ?? 'Not set' }}</strong>
        &nbsp;<span class="badge {{ $order->payment_status === 'paid' ? 'paid' : 'unpaid' }}">{{ $order->payment_status_label }}</span>
        @if ($order->transaction_id) <br>Transaction: {{ $order->transaction_id }} @endif
    </p>

    <footer class="small">
        {{ $settings->footer_text ?: 'Thank you for shopping with ' . $settings->site_name . '.' }}
    </footer>
</main>
</body>
</html>
