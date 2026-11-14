@extends('layouts.admin')

@section('title', $page->exists ? 'Edit page' : 'New page')

@section('content')
    @php
        $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label = 'block text-xs font-semibold text-slate-700 mb-1';
        $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
        $err   = 'text-rose-500 text-[11px] mt-1 block';
        $faq   = old('faq', $page->faq_items ?: [['q' => '', 'a' => '']]);
        $flag  = fn ($key, $default = false) => old($key, old('_token') ? null : $page->{$key} ?? $default) ? true : false;
    @endphp

    {{-- The text editor (Trix, free). Images are switched off on purpose. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.1.1/dist/trix.css">
    <script src="https://cdn.jsdelivr.net/npm/trix@2.1.1/dist/trix.umd.min.js"></script>
    <style>
        trix-toolbar .trix-button-group--file-tools { display: none; }
        trix-editor { min-height: 22rem; background: #fff; font-size: .8125rem; line-height: 1.7; border-color: #e2e8f0; border-radius: .75rem; }
        trix-editor h1 { font-size: 1.15rem; font-weight: 800; }
        trix-editor ul { list-style: disc; padding-left: 1.4rem; } trix-editor ol { list-style: decimal; padding-left: 1.4rem; }
        trix-editor a { color: #4f46e5; text-decoration: underline; }
    </style>
    <script>document.addEventListener('trix-file-accept', e => e.preventDefault());</script>

    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">{{ $page->exists ? $page->title : 'New page' }}</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $page->exists ? 'Last saved ' . $page->updated_at->diffForHumans() : 'Write the page, then choose where it shows.' }}</p>
            </div>
            <a href="{{ route('admin.pages.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to pages</a>
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                <ul class="list-disc pl-5 text-[11px] text-rose-600 space-y-0.5">@foreach ($errors->all() as $m)<li>{{ $m }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" method="POST"
              x-data="{ template: '{{ old('template', $page->template) }}', title: @js(old('title', $page->title)), slug: @js(old('slug', $page->slug)), touched: {{ $page->exists ? 'true' : 'false' }},
                        faq: @js(array_values($faq)),
                        autoSlug() { if (!this.touched) this.slug = this.title.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') } }"
              class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
            @csrf
            @if ($page->exists) @method('PUT') @endif

            {{-- ===== left ===== --}}
            <div class="lg:col-span-2 space-y-4">
                <section class="{{ $card }}">
                    <div>
                        <label class="{{ $label }}">Title *</label>
                        <input type="text" name="title" x-model="title" @input="autoSlug()" maxlength="150" required class="{{ $input }}">
                        @error('title') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="{{ $label }}">Page address</label>
                        <div class="flex items-center rounded-xl border border-slate-200 overflow-hidden {{ $page->is_system ? 'bg-slate-50' : '' }}">
                            <span class="px-3 text-[11px] text-slate-400 whitespace-nowrap border-r border-slate-200 py-2.5 bg-slate-50">{{ url('/pages') }}/</span>
                            <input type="text" name="slug" x-model="slug" @input="touched = true" maxlength="160" @disabled($page->is_system)
                                   class="w-full text-xs border-0 focus:ring-0 font-mono" placeholder="about-us">
                        </div>
                        @if($page->is_system)<p class="text-[10px] text-slate-400 mt-1">This page is linked from checkout or sign up, so its address is fixed.</p>
                        @else<p class="text-[10px] text-slate-400 mt-1">Letters, numbers and dashes. Made from the title if you leave it empty.</p>@endif
                        @error('slug') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <p class="{{ $label }}" id="page-type-label">Page type</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5" role="radiogroup" aria-labelledby="page-type-label">
                            @foreach ([['standard', 'Normal page', 'Text, like About us or Terms'], ['faq', 'FAQ', 'Questions that open and close'], ['contact', 'Contact', 'Your details and a message form']] as [$key, $name, $hint])
                                <label class="cursor-pointer">
                                    <input type="radio" name="template" value="{{ $key }}" x-model="template" class="sr-only peer">
                                    <span class="block h-full p-3 rounded-xl border border-slate-200 bg-white transition-colors hover:border-indigo-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50/60 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400">
                                        <span class="block text-xs font-bold text-slate-800">{{ $name }}</span>
                                        <span class="block text-[11px] text-slate-500 mt-0.5">{{ $hint }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1.5" x-show="template === 'contact'" x-cloak>Shows your address, phone, email and hours from Site Settings next to the form. Messages arrive in Messages.</p>
                        <p class="text-[10px] text-slate-400 mt-1.5" x-show="template === 'faq'" x-cloak>Add the questions in the box below. They open and close on the page and can be searched.</p>
                        @error('template') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </section>

                <section class="{{ $card }}">
                    <div>
                        <label class="{{ $label }}"><span x-text="template === 'standard' ? 'Page text' : 'Text above the page (optional)'"></span></label>
                        <input id="content" type="hidden" name="content" value="{{ old('content', $page->content) }}">
                        <trix-editor input="content" class="trix-content" aria-label="Page text"></trix-editor>
                        <p class="text-[10px] text-slate-400 mt-1.5">Use "Heading" (the H button) for section titles. Colours, sizes and pictures are not kept, so every page stays clean and matches your shop design.</p>
                        @error('content') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </section>

                {{-- FAQ items --}}
                <section class="{{ $card }}" x-show="template === 'faq'" x-cloak>
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-800">Questions and answers</h3>
                        <button type="button" @click="faq.push({ q: '', a: '' })" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">+ Add question</button>
                    </div>
                    <template x-for="(item, i) in faq" :key="i">
                        <div class="rounded-xl border border-slate-200 p-3 space-y-2 bg-slate-50/50">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-slate-400 w-5" x-text="i + 1"></span>
                                <input type="text" :name="`faq[${i}][q]`" x-model="item.q" maxlength="300" placeholder="Question" class="{{ $input }}">
                                <button type="button" @click="if (i > 0) faq.splice(i - 1, 2, faq[i], faq[i - 1])" class="p-1.5 text-slate-400 hover:text-slate-700" aria-label="Move up">&uarr;</button>
                                <button type="button" @click="if (i < faq.length - 1) faq.splice(i, 2, faq[i + 1], faq[i])" class="p-1.5 text-slate-400 hover:text-slate-700" aria-label="Move down">&darr;</button>
                                <button type="button" @click="faq.splice(i, 1)" class="p-1.5 text-slate-400 hover:text-rose-600" aria-label="Remove question">&times;</button>
                            </div>
                            <textarea :name="`faq[${i}][a]`" x-model="item.a" rows="3" maxlength="3000" placeholder="Answer" class="{{ $input }}"></textarea>
                        </div>
                    </template>
                    <p class="text-[10px] text-slate-400">Rows with an empty question or answer are skipped when you save.</p>
                </section>

                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">Search engines</h3>
                    <div>
                        <label class="{{ $label }}">Page title for Google <span class="font-normal text-slate-400">(optional)</span></label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" maxlength="160" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Description for Google <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea name="meta_description" rows="2" maxlength="300" class="{{ $input }}">{{ old('meta_description', $page->meta_description) }}</textarea>
                    </div>
                </section>
            </div>

            {{-- ===== right ===== --}}
            <aside class="space-y-4 lg:sticky lg:top-6">
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">Publish</h3>

                    <label class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-700">Visible to customers
                        <input type="checkbox" name="is_active" value="1" @checked($flag('is_active', true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                    <label class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-700">Link in the footer
                        <input type="checkbox" name="show_in_footer" value="1" @checked($flag('show_in_footer', true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>
                    <label class="flex items-center justify-between gap-3 text-xs font-semibold text-slate-700">Link in the top menu
                        <input type="checkbox" name="show_in_header" value="1" @checked($flag('show_in_header', false)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></label>

                    <div>
                        <label class="{{ $label }}">Order in the menus</label>
                        <input type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order', $page->sort_order) }}" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">Smaller numbers come first.</p>
                    </div>

                    <button type="submit" class="w-full px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $page->exists ? 'Save changes' : 'Create page' }}
                    </button>
                    @if($page->exists && $page->is_active)
                        <a href="{{ $page->url }}" target="_blank" class="block text-center text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">View on the shop &rarr;</a>
                    @endif
                </section>
            </aside>
        </form>
    </div>
@endsection
