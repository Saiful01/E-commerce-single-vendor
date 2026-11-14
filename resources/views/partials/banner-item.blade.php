{{-- Needs: $b (Slider), $meta (Slider::POSITIONS entry), $eager (bool) --}}
@php
    $url   = $b->resolved_url;
    $tag   = $url ? 'a' : 'div';
    $ratio = fn ($s) => preg_replace('/\s*×\s*/u', '/', $s);
    $pos   = [
        'left'   => 'items-start text-left',
        'center' => 'items-center text-center',
        'right'  => 'items-end text-right',
    ][$b->text_align ?: 'left'] ?? 'items-start text-left';
    $hasText = $b->show_text && ($b->title || $b->subtitle || $b->btn_text);
@endphp
<{{ $tag }}
    @if($url) href="{{ $url }}" @if($b->open_new_tab) target="_blank" rel="noopener" @endif @endif
    class="ar relative block w-full overflow-hidden bg-neutral-100"
    style="--r-m:{{ $ratio($meta['mobile_size']) }};--r-d:{{ $ratio($meta['size']) }}">
    <picture>
        <source media="(min-width:768px)" srcset="{{ $b->image_url }}">
        <img src="{{ $b->mobile_image_url }}" alt="{{ $b->title }}"
             class="absolute inset-0 h-full w-full object-cover"
             loading="{{ ($eager ?? false) ? 'eager' : 'lazy' }}" decoding="async">
    </picture>

    @if($hasText)
        <span class="absolute inset-0" style="background:rgba(0,0,0,{{ ($b->overlay_opacity ?? 0) / 100 }})"></span>
        <span class="absolute inset-0 flex flex-col justify-center gap-2 md:gap-4 px-5 md:px-14 {{ $pos }}"
              style="color:{{ $b->text_color ?: '#ffffff' }}">
            @if($b->title)
                <span class="font-display text-xl sm:text-3xl lg:text-5xl font-extrabold leading-tight max-w-[22ch]">{{ $b->title }}</span>
            @endif
            @if($b->subtitle)
                <span class="text-sm md:text-lg max-w-[40ch] opacity-90">{{ $b->subtitle }}</span>
            @endif
            @if($b->btn_text)
                <span class="mt-1 inline-block rounded-lg bg-white px-4 py-2 md:px-6 md:py-3 text-sm md:text-base font-semibold text-neutral-900">{{ $b->btn_text }}</span>
            @endif
        </span>
    @endif
</{{ $tag }}>
