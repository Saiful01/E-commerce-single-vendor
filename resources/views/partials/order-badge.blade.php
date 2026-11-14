{{-- Needs: $status (OrderStatus) --}}
@php $c = preg_match('/^#[0-9a-f]{3,8}$/i', (string) $status->color) ? $status->color : 'var(--primary)'; @endphp
<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
      style="color:{{ $c }};background:color-mix(in srgb, {{ $c }} 14%, white)">
    <span class="h-1.5 w-1.5 rounded-full" style="background:{{ $c }}"></span>{{ $status->name }}
</span>
