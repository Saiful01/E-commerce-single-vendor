{{-- Needs: $order with histories.status --}}
<ol class="relative space-y-5 border-l-2 border-black/10 pl-6">
    @foreach($order->histories as $h)
        @php $c = preg_match('/^#[0-9a-f]{3,8}$/i', (string) $h->status?->color) ? $h->status->color : 'var(--primary)'; @endphp
        <li class="relative">
            <span class="absolute -left-[1.95rem] top-1 h-3.5 w-3.5 rounded-full border-2 border-white" style="background:{{ $c }}"></span>
            <p class="font-semibold">{{ $h->status?->name ?? 'Update' }}</p>
            @if($h->note)<p class="text-sm text-neutral-600">{{ $h->note }}</p>@endif
            <p class="text-xs text-neutral-500">{{ $h->created_at?->format('d M Y, h:i A') }}</p>
        </li>
    @endforeach
</ol>
