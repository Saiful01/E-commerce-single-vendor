{{-- Needs: $order, $setting --}}
@php
    $due      = $order->due_amount;
    $payments = $order->payments()->with('gateway:id,name')->get();
    $pending  = $payments->where('status', 'pending')->sum('amount');
    $methods  = app(\App\Services\CartService::class)->gateways()->reject(fn ($g) => $g->driver === 'cod' || \App\Support\OnlineGateways::isOnline($g->driver))->values();
    $online   = \App\Support\OnlineGateways::activeGateways();
    $closed   = $order->status->restock_items;
    $details  = fn ($g) => collect(\App\Models\PaymentGateway::DRIVERS[$g->driver]['fields'] ?? [])->where('secret', false)
        ->map(fn ($f) => [$f['label'], $g->credentials[$f['key']] ?? null])->filter(fn ($r) => $r[1])->values();
    $cfg = ['due' => $due, 'gateway' => old('payment_gateway_id', $methods->first()?->id),
        'methods' => $methods->mapWithKeys(fn ($g) => [$g->id => ['instructions' => $g->instructions, 'details' => $details($g)]])];
@endphp

<section class="space-y-3 rounded-2xl bg-white p-5 text-sm" @if($due > 0 && ! $closed && $methods->isNotEmpty()) x-data="{ amount: {{ $due }}, gateway: {{ (int) $cfg['gateway'] }}, methods: {{ \Illuminate\Support\Js::from($cfg['methods']) }} }" @endif>
    <h2 class="font-display text-lg font-bold">Payments</h2>

    @if($due > 0 && ! $closed)
        <p class="rounded-xl bg-amber-50 p-3 text-amber-900">
            <b>{{ $setting->formatMoney($due) }}</b> is still due.
            @if($pending > 0) We are checking <b>{{ $setting->formatMoney($pending) }}</b> you already told us about. @endif
        </p>

        @foreach($online as $og)
            <a href="{{ route('payment.' . $og->driver . '.pay', $order->order_number) }}" class="btn btn-primary w-full"><i class="bi bi-credit-card"></i> Pay {{ $setting->formatMoney($due) }} with {{ \App\Support\OnlineGateways::label($og->driver) }}</a>
        @endforeach

        @if($methods->isNotEmpty())
            <form method="POST" action="{{ route('account.orders.pay', $order->order_number) }}" class="space-y-3">
                @csrf
                <p class="font-semibold">Paid something? Tell us.</p>
                <label class="block text-xs font-medium">Amount you sent
                    <input type="number" name="amount" step="0.01" min="1" max="{{ $due }}" x-model="amount" required class="mt-1 w-full rounded-xl border border-black/15 px-3 py-2.5 text-sm"></label>
                <div class="flex gap-2 text-xs">
                    <button type="button" @click="amount = {{ $due }}" class="rounded-lg border border-black/15 px-2.5 py-1 hover:border-[var(--primary)]">Everything left</button>
                    <button type="button" @click="amount = {{ round($due / 2, 2) }}" class="rounded-lg border border-black/15 px-2.5 py-1 hover:border-[var(--primary)]">Half</button>
                </div>
                <label class="block text-xs font-medium">Sent with
                    <select name="payment_gateway_id" x-model.number="gateway" class="mt-1 w-full rounded-xl border border-black/15 px-3 py-2.5 text-sm">
                        @foreach($methods as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                    </select></label>
                <template x-if="methods[gateway]">
                    <div class="space-y-1 rounded-xl bg-neutral-50 p-3 text-xs text-neutral-700">
                        <p class="whitespace-pre-line" x-text="methods[gateway].instructions" x-show="methods[gateway].instructions"></p>
                        <template x-for="d in methods[gateway].details" :key="d[0]"><p><span class="text-neutral-500" x-text="d[0] + ': '"></span><b x-text="d[1]"></b></p></template>
                    </div>
                </template>
                <label class="block text-xs font-medium">Transaction ID
                    <input name="transaction_id" required maxlength="100" value="{{ old('transaction_id') }}" class="mt-1 w-full rounded-xl border border-black/15 px-3 py-2.5 text-sm"></label>
                <button class="btn btn-primary w-full">Send payment details</button>
            </form>
        @endif
    @elseif($order->payment_status === 'paid')
        <p class="rounded-xl bg-green-50 p-3 text-green-800"><i class="bi bi-check-circle-fill"></i> This order is fully paid.</p>
    @endif

    @if($payments->isNotEmpty())
        <ul class="divide-y divide-black/5 text-xs">
            @foreach($payments as $p)
                <li class="flex items-start justify-between gap-3 py-2">
                    <div><p class="font-semibold">{{ $p->type === 'refund' ? 'Refund' : 'Payment' }} · {{ $p->gateway?->name ?? 'Not specified' }}</p>
                        <p class="text-neutral-500">{{ $p->created_at->format('d M Y') }} @if($p->transaction_id)· {{ $p->transaction_id }}@endif</p></div>
                    <div class="text-right"><p class="font-bold {{ $p->type === 'refund' ? 'text-red-600' : '' }}">{{ $p->type === 'refund' ? '-' : '' }}{{ $setting->formatMoney($p->amount) }}</p>
                        <p class="text-neutral-500">{{ ['pending' => 'Being checked', 'confirmed' => 'Received', 'rejected' => 'Not accepted'][$p->status] }}</p></div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
