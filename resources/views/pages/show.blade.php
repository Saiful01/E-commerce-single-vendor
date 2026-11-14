@extends('layouts.app')

@php
    $siteName = $setting->site_name ?: config('app.name');
    $faq      = collect($page->faq_items ?? []);
    $wa       = preg_replace('/\D/', '', (string) $setting->whatsapp_number);
    if ($wa && str_starts_with($wa, '0')) { $wa = '88' . $wa; }
    $socials  = ['facebook_url' => ['bi-facebook', 'Facebook'], 'instagram_url' => ['bi-instagram', 'Instagram'], 'youtube_url' => ['bi-youtube', 'YouTube'],
                 'tiktok_url' => ['bi-tiktok', 'TikTok'], 'twitter_url' => ['bi-twitter-x', 'X'], 'linkedin_url' => ['bi-linkedin', 'LinkedIn']];
    $input    = 'w-full rounded-xl border bg-white px-3.5 py-3 text-sm placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-[color-mix(in_srgb,var(--primary)_25%,transparent)] ';
@endphp

@section('title', ($page->meta_title ?: $page->title) . ' | ' . $siteName)
@section('meta_description', $page->meta_description ?: $page->excerpt)

@push('head')
    <style>
        .cms { color: #374151; line-height: 1.85; font-size: 1.0125rem; }
        .cms > :first-child { margin-top: 0; }
        .cms h2 { font-family: 'Bricolage Grotesque', 'Hind Siliguri', sans-serif; font-size: 1.5rem; font-weight: 800; color: var(--text); margin: 2.25rem 0 .75rem; line-height: 1.3; }
        .cms h3 { font-size: 1.2rem; font-weight: 700; color: var(--text); margin: 1.75rem 0 .5rem; }
        .cms h4 { font-weight: 700; color: var(--text); margin: 1.25rem 0 .4rem; }
        .cms p, .cms div { margin: .8rem 0; }
        .cms ul { list-style: disc; padding-left: 1.5rem; margin: .8rem 0; }
        .cms ol { list-style: decimal; padding-left: 1.5rem; margin: .8rem 0; }
        .cms li { margin: .35rem 0; padding-left: .2rem; }
        .cms li::marker { color: var(--primary); }
        .cms a { color: var(--primary); text-decoration: underline; text-underline-offset: 3px; }
        .cms blockquote { border-left: 4px solid var(--primary); background: color-mix(in srgb, var(--primary) 7%, white); padding: .75rem 1.1rem; margin: 1.25rem 0; border-radius: 0 .75rem .75rem 0; }
        .cms pre { background: #f4f4f2; padding: 1rem; border-radius: .75rem; overflow: auto; font-size: .875rem; }
        .cms hr { margin: 2rem 0; border: 0; border-top: 1px solid rgba(0,0,0,.1); }
        .cms strong, .cms b { color: var(--text); }
    </style>

    @if($page->template === 'faq' && $faq->isNotEmpty())
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org', '@type' => 'FAQPage',
            'mainEntity' => $faq->map(fn ($i) => ['@type' => 'Question', 'name' => $i['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['a']]])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endif
@endpush

@section('content')
    {{-- Title band --}}
    <section class="soft border-b border-black/5">
        <div class="wrap py-8 md:py-12">
            <nav class="mb-3 flex items-center gap-1 text-xs text-neutral-500 md:text-sm" aria-label="Breadcrumb">
                <a href="{{ url('/') }}" class="hover:text-brand">Home</a><i class="bi bi-chevron-right text-[10px]"></i><span class="text-ink">{{ $page->title }}</span>
            </nav>
            <h1 class="font-display text-3xl font-extrabold md:text-5xl">{{ $page->title }}</h1>
            @if($page->template === 'standard')<p class="mt-3 text-sm text-neutral-500">Last updated {{ $page->updated_at->format('d F Y') }}</p>@endif
        </div>
    </section>

    @php $sidebar = $others->isNotEmpty() && $page->template !== 'contact'; @endphp
    <div class="wrap grid items-start gap-8 py-8 md:py-12 {{ $sidebar ? 'lg:grid-cols-[1fr_17rem]' : '' }}">
        <div class="min-w-0 space-y-8">

            @if(trim((string) $page->content) !== '')
                @if($page->template === 'contact')
                    <article class="cms max-w-2xl text-lg text-neutral-600">{!! $page->content !!}</article>
                @else
                    <article class="cms max-w-3xl rounded-2xl bg-white p-6 md:p-10">{!! $page->content !!}</article>
                @endif
            @endif

            {{-- ===== FAQ ===== --}}
            @if($page->template === 'faq')
                <section class="max-w-3xl space-y-4" x-data="{ q: '', open: null, items: @js($faq->values()),
                            get shown() { const t = this.q.trim().toLowerCase(); return t ? this.items.filter(i => (i.q + ' ' + i.a).toLowerCase().includes(t)) : this.items } }">
                    @if($faq->count() > 5)
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400"></i>
                            <input type="search" x-model="q" placeholder="Search the questions" class="{{ $input }} border-black/10 pl-11" aria-label="Search the questions">
                        </div>
                    @endif

                    <div class="divide-y divide-black/5 overflow-hidden rounded-2xl bg-white">
                        <template x-for="(item, i) in shown" :key="item.q">
                            <div>
                                <button type="button" @click="open = open === item.q ? null : item.q" :aria-expanded="open === item.q"
                                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold md:px-7 md:py-5">
                                    <span x-text="item.q"></span>
                                    <i class="bi bi-plus-lg shrink-0 text-brand transition-transform duration-200" :class="open === item.q && 'rotate-45'"></i>
                                </button>
                                <div x-show="open === item.q" x-collapse x-cloak>
                                    <p class="whitespace-pre-line px-5 pb-5 leading-relaxed text-neutral-600 md:px-7" x-text="item.a"></p>
                                </div>
                            </div>
                        </template>
                        <p x-show="shown.length === 0" x-cloak class="px-7 py-8 text-center text-neutral-500">Nothing matches that. Try another word or <a href="{{ url('/pages/contact-us') }}" class="font-semibold text-brand underline">ask us directly</a>.</p>
                    </div>
                </section>
            @endif

            {{-- ===== Contact ===== --}}
            @if($page->template === 'contact')
                @php
                    $rows = array_values(array_filter([
                        $setting->phone_number  ? ['bi-telephone', 'Call us', $setting->phone_number, 'tel:' . $setting->phone_number] : null,
                        $setting->primary_email ? ['bi-envelope', 'Email', $setting->primary_email, 'mailto:' . $setting->primary_email] : null,
                        $setting->address       ? ['bi-geo-alt', 'Visit us', $setting->address, 'https://www.google.com/maps/search/?api=1&query=' . urlencode($setting->address)] : null,
                        $setting->business_hours ? ['bi-clock', 'Open hours', $setting->business_hours, null] : null,
                    ]));
                @endphp

                <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-black/5 lg:grid lg:grid-cols-[2fr_3fr]">
                    {{-- Details --}}
                    <div class="relative overflow-hidden bg-brand p-8 text-white md:p-10">
                        <span class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full border-[40px] border-white/10"></span>
                        <span class="pointer-events-none absolute -bottom-24 -left-16 h-56 w-56 rounded-full border-[36px] border-white/10"></span>

                        <div class="relative">
                            <h2 class="font-display text-2xl font-extrabold md:text-3xl">Get in touch</h2>
                            <p class="mt-2 text-white/80">Questions about an order, a product or a return? Call, message or write to us.</p>

                            @if($rows)
                                <ul class="mt-8 space-y-6">
                                    @foreach($rows as [$icon, $label, $value, $href])
                                        <li class="flex items-start gap-4">
                                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-white/15 text-lg"><i class="bi {{ $icon }}"></i></span>
                                            <div class="min-w-0">
                                                <p class="text-sm text-white/70">{{ $label }}</p>
                                                @if($href)
                                                    <a href="{{ $href }}" @if(str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif class="break-words font-semibold hover:underline">{{ $value }}</a>
                                                @else
                                                    <p class="whitespace-pre-line font-semibold">{{ $value }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-8 rounded-2xl bg-white/10 p-4 text-sm text-white/90">Send us a message with the form and we will get back to you on the number you give.</p>
                            @endif

                            @if($wa)
                                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 font-semibold text-neutral-900 hover:bg-white/90"><i class="bi bi-whatsapp text-lg text-[#25d366]"></i> Chat on WhatsApp</a>
                            @endif

                            @php $anySocial = collect($socials)->keys()->contains(fn ($f) => $setting->$f); @endphp
                            @if($anySocial)
                                <div class="mt-8 flex flex-wrap gap-2">
                                    @foreach($socials as $field => [$icon, $name])
                                        @if($setting->$field)<a href="{{ $setting->$field }}" target="_blank" rel="noopener" aria-label="{{ $name }}" class="grid h-10 w-10 place-items-center rounded-full bg-white/15 hover:bg-white/25"><i class="bi {{ $icon }}"></i></a>@endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Form --}}
                    <form id="contact-form" method="POST" action="{{ route('contact.store') }}" x-data="{ busy: false }" @submit="busy = true" class="relative space-y-5 p-8 md:p-10">
                        @csrf
                        <div>
                            <h2 class="font-display text-2xl font-extrabold md:text-3xl">Send us a message</h2>
                            <p class="mt-1 text-neutral-500">Fields marked with * are needed.</p>
                        </div>

                        @if($errors->any())<ul class="rounded-xl bg-red-50 p-4 text-sm text-red-700" role="alert">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif

                        {{-- spam trap: real visitors never see or fill this --}}
                        <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                            <label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <label class="block text-sm font-medium">Your name *
                                <input name="name" value="{{ old('name', auth()->user()?->name) }}" required autocomplete="name" class="{{ $input }} mt-1.5 border-black/15"></label>
                            <label class="block text-sm font-medium">Mobile number *
                                <input name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" class="{{ $input }} mt-1.5 border-black/15"></label>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <label class="block text-sm font-medium">Email <span class="font-normal text-neutral-500">(optional)</span>
                                <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" autocomplete="email" class="{{ $input }} mt-1.5 border-black/15"></label>
                            <label class="block text-sm font-medium">Subject <span class="font-normal text-neutral-500">(optional)</span>
                                <input name="subject" value="{{ old('subject') }}" maxlength="150" placeholder="Order number or topic" class="{{ $input }} mt-1.5 border-black/15"></label>
                        </div>
                        <label class="block text-sm font-medium">Message *
                            <textarea name="message" rows="5" required minlength="10" maxlength="3000" placeholder="How can we help?" class="{{ $input }} mt-1.5 border-black/15">{{ old('message') }}</textarea></label>

                        <button class="btn btn-primary w-full !py-3.5 text-base" :disabled="busy"><i class="bi" :class="busy ? 'bi-arrow-repeat animate-spin' : 'bi-send'"></i> <span x-text="busy ? 'Sending...' : 'Send message'"></span></button>
                    </form>
                </section>

                {{-- more pages, as chips under the contact block --}}
                @if($others->isNotEmpty())
                    <nav class="flex flex-wrap items-center gap-2 text-sm" aria-label="More information">
                        <span class="mr-1 text-neutral-500">Also see:</span>
                        @foreach($others as $o)<a href="{{ url('/pages/' . $o->slug) }}" class="rounded-full bg-white px-4 py-2 text-neutral-700 ring-1 ring-black/5 hover:text-brand">{{ $o->title }}</a>@endforeach
                    </nav>
                @endif
            @endif
        </div>

        {{-- Other pages --}}
        @if($sidebar)
            <aside class="lg:sticky lg:top-28">
                <div class="rounded-2xl bg-white p-5">
                    <p class="mb-2 font-display text-lg font-bold">More information</p>
                    <ul class="space-y-0.5 text-sm">
                        @foreach($others as $o)
                            <li><a href="{{ url('/pages/' . $o->slug) }}" class="flex items-center justify-between rounded-lg px-3 py-2 text-neutral-700 hover:bg-neutral-50 hover:text-brand">{{ $o->title }} <i class="bi bi-chevron-right text-[10px] opacity-50"></i></a></li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        @endif
    </div>
@endsection
