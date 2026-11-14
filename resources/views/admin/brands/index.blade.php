@extends('layouts.admin')

@section('title', 'Brand Management')

@section('content')
    <div class="space-y-5" x-data="brandIndex()" @keydown.escape.window="close()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Brands</h2>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $stats['total'] }} {{ Str::plural('brand', $stats['total']) }}
                    <span class="text-slate-400">·</span> {{ $stats['active'] }} active
                    <span class="text-slate-400">·</span> {{ $stats['featured'] }} featured
                </p>
            </div>
            <a href="{{ route('admin.brands.create') }}"
               class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add brand
            </a>
        </div>

        {{-- Search & filters --}}
        <form method="GET" action="{{ route('admin.brands.index') }}" x-ref="filter"
              class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Search by name, slug or description"
                       @input.debounce.500ms="$refs.filter.submit()"
                       @if ($q !== '') x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)" @endif
                       class="w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <select name="status" @change="$refs.filter.submit()" class="text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All brands</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                <option value="featured" @selected($status === 'featured')>Featured</option>
            </select>

            @if ($q !== '' || $status)
                <a href="{{ route('admin.brands.index') }}" class="px-3 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 rounded-xl border border-slate-200 text-center">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Brand</th>
                        <th class="py-3.5 px-6">Website</th>
                        <th class="py-3.5 px-6">Products</th>
                        <th class="py-3.5 px-6 text-center">Featured</th>
                        <th class="py-3.5 px-6 text-center">Active</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($brands as $brand)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-6">
                                <button type="button" @click="open({{ $brand->id }})" class="flex items-center gap-3 text-left group min-w-0">
                                    @if ($brand->logo_url)
                                        <img src="{{ $brand->logo_url }}" class="w-11 h-11 rounded-xl object-contain border border-slate-200 bg-white p-1 shrink-0" alt="{{ $brand->name }}">
                                    @else
                                        <span class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 grid place-items-center text-slate-500 font-bold uppercase shrink-0">{{ mb_substr($brand->name, 0, 1) }}</span>
                                    @endif
                                    <span class="min-w-0">
                                    <span class="block font-bold text-slate-800 group-hover:text-indigo-600 transition-colors truncate">{{ $brand->name }}</span>
                                    <span class="block text-[10px] font-mono text-slate-400 truncate">/{{ $brand->slug }}</span>
                                </span>
                                </button>
                            </td>

                            <td class="py-3 px-6">
                                @if ($brand->website_url)
                                    <a href="{{ $brand->website_url }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-800 truncate inline-block max-w-[12rem] align-bottom">
                                        {{ preg_replace('#^https?://(www\.)?#', '', rtrim($brand->website_url, '/')) }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Not set</span>
                                @endif
                            </td>

                            <td class="py-3 px-6">
                                @if ($brand->products_count > 0)
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">{{ $brand->products_count }} {{ Str::plural('product', $brand->products_count) }}</span>
                                @else
                                    <span class="text-slate-400">None</span>
                                @endif
                            </td>

                            {{-- Two AJAX switches share one handler --}}
                            @foreach ([['is_featured', 'Featured', 'peer-checked:bg-amber-500'], ['is_active', 'Active', 'peer-checked:bg-emerald-500']] as [$field, $title, $color])
                                <td class="py-3 px-6 text-center">
                                    <div x-data="{ on: {{ $brand->{$field} ? 'true' : 'false' }}, busy: false }" class="inline-block">
                                        <label class="relative inline-flex items-center cursor-pointer" title="{{ $title }}">
                                            <input type="checkbox" class="sr-only peer" :checked="on" :disabled="busy" aria-label="{{ $title }}: {{ $brand->name }}"
                                                   @change="busy = true; toggle({{ $brand->id }}, '{{ $field }}', $event.target).then(v => { on = v; busy = false })">
                                            <span class="w-9 h-5 bg-slate-200 rounded-full {{ $color }} peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                                        </label>
                                    </div>
                                </td>
                            @endforeach

                            <td class="py-3 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="open({{ $brand->id }})" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">View</button>
                                    <a href="{{ route('admin.brands.edit', $brand) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</a>

                                    <form id="delete-brand-{{ $brand->id }}" action="{{ route('admin.brands.destroy', $brand) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                onclick="confirmDelete('delete-brand-{{ $brand->id }}', 'A brand can only be deleted when no product uses it.')"
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                @if ($q !== '' || $status)
                                    No brands match your filters. <a href="{{ route('admin.brands.index') }}" class="text-indigo-600 font-semibold">Clear filters</a>
                                @else
                                    No brands yet. <a href="{{ route('admin.brands.create') }}" class="text-indigo-600 font-semibold">Add the first one</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $brands->links() }}
            </div>
        </div>

        {{-- ===================== DETAILS MODAL ===================== --}}
        <div x-show="b" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="b ? b.name : ''">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" x-show="b" x-transition.opacity @click="close()"></div>

            <div class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl"
                 x-show="b" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                <template x-if="b">
                    <div>
                        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-800 truncate" x-text="b.name"></h3>
                                <p class="text-[11px] text-slate-400 font-mono mt-0.5 truncate" x-text="`/${b.slug}`"></p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="b.edit_url" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700">Edit brand</a>
                                <button type="button" @click="close()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 space-y-6 text-xs">
                            <div class="flex gap-4">
                                <img x-show="b.logo" :src="b.logo" class="w-28 h-28 rounded-2xl object-contain border border-slate-200 bg-white p-3 shrink-0" :alt="b.name">
                                <div x-show="!b.logo" class="w-28 h-28 rounded-2xl bg-slate-100 border border-slate-200 grid place-items-center text-[11px] text-slate-400 shrink-0">No logo</div>

                                <div class="space-y-3 min-w-0">
                                    <div class="flex flex-wrap gap-2">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                                          :class="b.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                          x-text="b.is_active ? 'Active' : 'Inactive'"></span>
                                        <span x-show="b.is_featured" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Featured</span>
                                    </div>
                                    <p class="text-slate-600 leading-relaxed whitespace-pre-line" x-show="b.description" x-text="b.description"></p>
                                    <p class="text-slate-400" x-show="!b.description">No description.</p>
                                </div>
                            </div>

                            <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3 bg-slate-50 rounded-xl p-4">
                                <div><dt class="text-slate-400">Products</dt><dd class="font-semibold text-slate-800" x-text="b.products_count"></dd></div>
                                <div><dt class="text-slate-400">Display order</dt><dd class="font-semibold text-slate-800" x-text="b.sort_order"></dd></div>
                                <div>
                                    <dt class="text-slate-400">Website</dt>
                                    <dd class="font-semibold">
                                        <a x-show="b.website_url" :href="b.website_url" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-800 break-all" x-text="b.website_url"></a>
                                        <span x-show="!b.website_url" class="text-slate-800">Not set</span>
                                    </dd>
                                </div>
                                <div class="col-span-2 md:col-span-3"><dt class="text-slate-400">Link</dt><dd class="font-mono text-slate-800 break-all" x-text="b.url"></dd></div>
                                <div><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800" x-text="b.created_at || '-'"></dd></div>
                                <div><dt class="text-slate-400">Last updated</dt><dd class="font-semibold text-slate-800" x-text="b.updated_at || '-'"></dd></div>
                            </dl>

                            <div>
                                <h4 class="font-bold text-slate-800 mb-2">Search engine listing</h4>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                    <div>
                                        <p class="text-[11px] text-slate-500 truncate" x-text="b.url"></p>
                                        <p class="text-[15px] leading-snug text-indigo-700 font-medium" x-text="b.seo.meta_title || b.name"></p>
                                        <p class="text-slate-600" x-text="b.seo.meta_description || b.description || 'No meta description set.'"></p>
                                    </div>
                                    <dl class="pt-3 border-t border-slate-200">
                                        <dt class="text-slate-400">Meta keywords</dt>
                                        <dd class="font-semibold text-slate-800" x-text="b.seo.meta_keywords || 'Not set'"></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        const BRAND_DETAILS = @json($details);

        function brandIndex() {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const toast = (icon, text) => {
                if (typeof Swal === 'undefined') return;
                Swal.fire({ icon, text, toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
            };

            return {
                b: null,

                open(id) {
                    this.b = BRAND_DETAILS.find(x => x.id === id) || null;
                    if (this.b) document.body.classList.add('overflow-hidden');
                },
                close() {
                    this.b = null;
                    document.body.classList.remove('overflow-hidden');
                },

                // field: is_active | is_featured
                toggle(id, field, checkbox) {
                    const wanted = checkbox.checked;

                    return fetch(`{{ url('admin/brands') }}/${id}/toggle`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ field }),
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || !data.success) throw new Error(data.message || `Request failed (HTTP ${res.status})`);

                            const detail = BRAND_DETAILS.find(x => x.id === id);
                            if (detail) detail[field] = data.value;

                            toast('success', data.message);
                            return data.value;
                        })
                        .catch(err => {
                            checkbox.checked = !wanted; // revert on failure
                            toast('error', err.message);
                            return !wanted;
                        });
                },
            };
        }
    </script>
@endsection
