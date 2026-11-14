@extends('layouts.admin')

@section('title', 'Category Management')

@section('content')
    <div class="space-y-5" x-data="categoryIndex()" @keydown.escape.window="close()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Categories</h2>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $stats['total'] }} {{ Str::plural('category', $stats['total']) }}
                    <span class="text-slate-400">·</span> {{ $stats['active'] }} active
                    <span class="text-slate-400">·</span> {{ $stats['featured'] }} featured
                </p>
            </div>
            <a href="{{ route('admin.categories.create') }}"
               class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add category
            </a>
        </div>

        {{-- Search & filters --}}
        <form method="GET" action="{{ route('admin.categories.index') }}" x-ref="filter"
              class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Search by name, slug or description"
                       @input.debounce.500ms="$refs.filter.submit()"
                       @if ($q !== '') x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)" @endif
                       class="w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <select name="status" @change="$refs.filter.submit()" class="text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">All categories</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                <option value="featured" @selected($status === 'featured')>Featured</option>
                <option value="hidden" @selected($status === 'hidden')>Hidden from menu</option>
            </select>

            @if ($filtering)
                <a href="{{ route('admin.categories.index') }}" class="px-3 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 rounded-xl border border-slate-200 text-center">Clear</a>
            @endif
        </form>

        @if (! $filtering && $rows->count() > 1)
            <p class="text-[11px] text-slate-500 -mt-2">Sub-categories sit under their parent. Use the display order in each category to rearrange them.</p>
        @endif

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Category</th>
                        <th class="py-3.5 px-6">Products</th>
                        <th class="py-3.5 px-6 text-center">Featured</th>
                        <th class="py-3.5 px-6 text-center">In menu</th>
                        <th class="py-3.5 px-6 text-center">Active</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($rows as $category)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-6">
                                <div class="flex items-center gap-3" style="padding-left: {{ $filtering ? 0 : $category->depth * 24 }}px">
                                    @if (! $filtering && $category->depth > 0)
                                        <span class="text-slate-300 select-none" aria-hidden="true">└</span>
                                    @endif

                                    <button type="button" @click="open({{ $category->id }})" class="flex items-center gap-3 text-left group min-w-0">
                                        @if ($category->image_url)
                                            <img src="{{ $category->image_url }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shrink-0" alt="{{ $category->name }}">
                                        @else
                                            <span class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 grid place-items-center text-slate-400 shrink-0">
                                            <svg class="w-4.5 h-4.5" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                        </span>
                                        @endif
                                        <span class="min-w-0">
                                        <span class="block font-bold text-slate-800 group-hover:text-indigo-600 transition-colors truncate">{{ $category->name }}</span>
                                        <span class="block text-[10px] font-mono text-slate-400 truncate">
                                            @if ($filtering && $category->depth > 0) {{ $category->path }} @else /{{ $category->slug }} @endif
                                        </span>
                                    </span>
                                    </button>
                                </div>
                            </td>

                            <td class="py-3 px-6">
                                @if ($category->products_count > 0)
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">{{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}</span>
                                @else
                                    <span class="text-slate-400">None</span>
                                @endif
                                @if ($category->children_count > 0)
                                    <span class="ml-1 text-[10px] text-slate-400">{{ $category->children_count }} sub</span>
                                @endif
                            </td>

                            {{-- Three AJAX switches share one handler --}}
                            @foreach ([['is_featured', 'Featured', 'peer-checked:bg-amber-500'], ['show_in_menu', 'Shown in menu', 'peer-checked:bg-indigo-600'], ['is_active', 'Active', 'peer-checked:bg-emerald-500']] as [$field, $title, $color])
                                <td class="py-3 px-6 text-center">
                                    <div x-data="{ on: {{ $category->{$field} ? 'true' : 'false' }}, busy: false }" class="inline-block">
                                        <label class="relative inline-flex items-center cursor-pointer" title="{{ $title }}">
                                            <input type="checkbox" class="sr-only peer" :checked="on" :disabled="busy" aria-label="{{ $title }}: {{ $category->name }}"
                                                   @change="busy = true; toggle({{ $category->id }}, '{{ $field }}', $event.target).then(v => { on = v; busy = false })">
                                            <span class="w-9 h-5 bg-slate-200 rounded-full {{ $color }} peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                                        </label>
                                    </div>
                                </td>
                            @endforeach

                            <td class="py-3 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="open({{ $category->id }})" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">View</button>
                                    <a href="{{ route('admin.categories.create', ['parent' => $category->id]) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="Add a sub-category under {{ $category->name }}">+ Sub</a>
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</a>

                                    <form id="delete-category-{{ $category->id }}" action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                onclick="confirmDelete('delete-category-{{ $category->id }}', 'A category can only be deleted when it has no sub-categories and no products.')"
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                @if ($filtering)
                                    No categories match your filters. <a href="{{ route('admin.categories.index') }}" class="text-indigo-600 font-semibold">Clear filters</a>
                                @else
                                    No categories yet. <a href="{{ route('admin.categories.create') }}" class="text-indigo-600 font-semibold">Add the first one</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===================== DETAILS MODAL ===================== --}}
        <div x-show="c" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="c ? c.name : ''">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" x-show="c" x-transition.opacity @click="close()"></div>

            <div class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl"
                 x-show="c" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                <template x-if="c">
                    <div>
                        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-slate-800 truncate" x-text="c.name"></h3>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate" x-text="c.path"></p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="c.edit_url" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700">Edit category</a>
                                <button type="button" @click="close()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 space-y-6 text-xs">
                            <div class="flex gap-4">
                                <img x-show="c.image" :src="c.image" class="w-28 h-28 rounded-2xl object-cover border border-slate-200 shrink-0" :alt="c.name">
                                <div x-show="!c.image" class="w-28 h-28 rounded-2xl bg-slate-100 border border-slate-200 grid place-items-center text-[11px] text-slate-400 shrink-0">No image</div>

                                <div class="space-y-3 min-w-0">
                                    <div class="flex flex-wrap gap-2">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                                          :class="c.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                          x-text="c.is_active ? 'Active' : 'Inactive'"></span>
                                        <span x-show="c.is_featured" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Featured</span>
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                                              :class="c.show_in_menu ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                              x-text="c.show_in_menu ? 'In menu' : 'Hidden from menu'"></span>
                                    </div>
                                    <p class="text-slate-600 leading-relaxed whitespace-pre-line" x-show="c.description" x-text="c.description"></p>
                                    <p class="text-slate-400" x-show="!c.description">No description.</p>
                                </div>
                            </div>

                            <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3 bg-slate-50 rounded-xl p-4">
                                <div><dt class="text-slate-400">Parent</dt><dd class="font-semibold text-slate-800" x-text="c.parent || 'Top level'"></dd></div>
                                <div><dt class="text-slate-400">Products</dt><dd class="font-semibold text-slate-800" x-text="c.products_count"></dd></div>
                                <div><dt class="text-slate-400">Display order</dt><dd class="font-semibold text-slate-800" x-text="c.sort_order"></dd></div>
                                <div class="col-span-2 md:col-span-3"><dt class="text-slate-400">Link</dt><dd class="font-mono text-slate-800 break-all" x-text="c.url"></dd></div>
                                <div><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800" x-text="c.created_at || '-'"></dd></div>
                                <div><dt class="text-slate-400">Last updated</dt><dd class="font-semibold text-slate-800" x-text="c.updated_at || '-'"></dd></div>
                            </dl>

                            <div x-show="c.children.length > 0">
                                <h4 class="font-bold text-slate-800 mb-2" x-text="`Sub-categories (${c.children.length})`"></h4>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="n in c.children" :key="n">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700" x-text="n"></span>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-bold text-slate-800 mb-2">Search engine listing</h4>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                    <div>
                                        <p class="text-[11px] text-slate-500 truncate" x-text="c.url"></p>
                                        <p class="text-[15px] leading-snug text-indigo-700 font-medium" x-text="c.seo.meta_title || c.name"></p>
                                        <p class="text-slate-600" x-text="c.seo.meta_description || c.description || 'No meta description set.'"></p>
                                    </div>
                                    <dl class="pt-3 border-t border-slate-200">
                                        <dt class="text-slate-400">Meta keywords</dt>
                                        <dd class="font-semibold text-slate-800" x-text="c.seo.meta_keywords || 'Not set'"></dd>
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
        const CATEGORY_DETAILS = @json($details);

        function categoryIndex() {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const toast = (icon, text) => {
                if (typeof Swal === 'undefined') return;
                Swal.fire({ icon, text, toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
            };

            return {
                c: null,

                open(id) {
                    this.c = CATEGORY_DETAILS.find(x => x.id === id) || null;
                    if (this.c) document.body.classList.add('overflow-hidden');
                },
                close() {
                    this.c = null;
                    document.body.classList.remove('overflow-hidden');
                },

                // field: is_active | is_featured | show_in_menu
                toggle(id, field, checkbox) {
                    const wanted = checkbox.checked;

                    return fetch(`{{ url('admin/categories') }}/${id}/toggle`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ field }),
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || !data.success) throw new Error(data.message || `Request failed (HTTP ${res.status})`);

                            const detail = CATEGORY_DETAILS.find(x => x.id === id);
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
