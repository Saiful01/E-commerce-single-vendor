@props(['banner', 'primary' => '#4F46E5', 'large' => false, 'eager' => false])

@php
    $url = $banner->resolved_url;

    $align = [
        'left'   => 'items-start text-left',
        'center' => 'items-center text-center',
        'right'  => 'items-end text-right',
    ][$banner->text_align] ?? 'items-start text-left';
@endphp

@if ($url)
    <a href="{{ $url }}" @if ($banner->open_new_tab) target="_blank" rel="noopener" @endif
        {{ $attributes->merge(['class' => 'relative block overflow-hidden']) }}>
        @else
            <div {{ $attributes->merge(['class' => 'relative block overflow-hidden']) }}>
                @endif

                <picture>
                    @if ($banner->mobile_image)
                        <source media="(max-width: 767px)" srcset="{{ $banner->mobile_image_url }}">
                    @endif
                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}"
                         class="absolute inset-0 w-full h-full object-cover" @unless ($eager) loading="lazy" @endunless>
                </picture>

                @if ($banner->show_text)
                    <div class="absolute inset-0" style="background: rgba(0,0,0,{{ $banner->overlay_opacity / 100 }})"></div>

                    <div class="relative h-full flex flex-col justify-center gap-2 p-5 md:p-10 {{ $align }}" style="color: {{ $banner->text_color }}">
                        <h2 class="font-bold leading-tight {{ $large ? 'text-2xl md:text-5xl' : 'text-lg md:text-2xl' }}">{{ $banner->title }}</h2>

                        @if ($banner->subtitle)
                            <p class="{{ $large ? 'text-sm md:text-lg' : 'text-xs md:text-sm' }} opacity-90">{{ $banner->subtitle }}</p>
                        @endif

                        @if ($url && $banner->btn_text)
                            <span class="mt-2 inline-block px-5 py-2.5 rounded-lg text-sm font-semibold text-white" style="background: {{ $primary }}">
                    {{ $banner->btn_text }}
                </span>
                        @endif
                    </div>
        @endif

        @if ($url)
    </a>
    @else
        </div>
@endif
