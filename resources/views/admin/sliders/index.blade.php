@extends('layouts.admin')

@section('title', 'Banners & Sliders')

@section('content')
    @php
        $positions  = \App\Models\Slider::POSITIONS;
        $canReorder = (bool) $position && $sliders->count() > 1;

        $stateStyles = [
            'live'      => ['Live',      'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'scheduled' => ['Scheduled', 'bg-sky-50 text-sky-700 border-sky-200'],
            'expired'   => ['Expired',   'bg-amber-50 text-amber-700 border-amber-200'],
            'inactive'  => ['Inactive',  'bg-slate-100 text-slate-600 border-slate-200'],
        ];

        $tabBase   = 'px-3.5 py-2 text-xs font-semibold rounded-xl border whitespace-nowrap transition-colors';
        $tabOn     = 'bg-indigo-600 text-white border-indigo-600 shadow-sm';
        $tabOff    = 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600';
    @endphp

    <div class="space-y-5" x-data="sliderList({ canReorder: {{ $canReorder ? 'true' : 'false' }}, reorderUrl: '{{ route('admin.sliders.reorder') }}' })">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Banners and sliders</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $total }} {{ Str::plural('banner', $total) }} across your storefront</p>
            </div>
            <a href="{{ route('admin.sliders.create', array_filter(['position' => $position])) }}"
               class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add banner
            </a>
        </div>

        {{-- Position tabs --}}
        <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="Banner positions">
            <a href="{{ route('admin.sliders.index', array_filter(['q' => $q, 'status' => $status])) }}"
               class="{{ $tabBase }} {{ $position ? $tabOff : $tabOn }}">
                All <span class="opacity-70 ml-1">{{ $total }}</span>
            </a>
            @foreach ($positions as $key => $p)
                <a href="{{ route('admin.sliders.index', array_filter(['position' => $key, 'q' => $q, 'status' => $status])) }}"
                   class="{{ $tabBase }} {{ $position === $key ? $tabOn : $tabOff }}">
                    {{ $p['label'] }} <span class="opacity-70 ml-1">{{ $counts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Search & status --}}
        <form method="GET" action="{{ route('admin.sliders.index') }}" x-ref="filter"
              class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2">
            @if ($position) <input type="hidden" name="position" value="{{ $position }}"> @endif

            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Search by title, subtitle or button text"
                       @input.debounce.500ms="$refs.filter.submit()"
                       @if ($q !== '') x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)" @endif
                       class="w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <select name="status" @change="$refs.filter.submit()" class="text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Any status</option>
                @foreach ($stateStyles as $key => [$name])
                    <option value="{{ $key }}" @selected($status === $key)>{{ $name }}</option>
                @endforeach
            </select>

            @if ($q !== '' || $status)
                <a href="{{ route('admin.sliders.index', array_filter(['position' => $position])) }}" class="px-3 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 rounded-xl border border-slate-200 text-center">Clear</a>
            @endif
        </form>

        @if ($position)
            <p class="text-[11px] text-slate-500 -mt-2">
                {{ $positions[$position]['hint'] }} Recommended size {{ $positions[$position]['size'] }} px.
                @if ($canReorder) <span class="font-semibold text-slate-700">Drag the handle to change the order.</span> @endif
            </p>
        @elseif ($sliders->count() > 1)
            <p class="text-[11px] text-slate-500 -mt-2">Pick a position above to reorder its banners by dragging.</p>
        @endif

        {{-- List --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            @if ($sliders->isEmpty())
                <div class="text-center py-14 px-6">
                    <div class="mx-auto w-12 h-12 rounded-2xl bg-slate-100 grid place-items-center text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-700 mt-3">
                        @if ($q !== '' || $status) No banners match your filters @else No banners here yet @endif
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        @if ($q !== '' || $status)
                            <a href="{{ route('admin.sliders.index', array_filter(['position' => $position])) }}" class="text-indigo-600 font-semibold">Clear filters</a>
                        @else
                            <a href="{{ route('admin.sliders.create', array_filter(['position' => $position])) }}" class="text-indigo-600 font-semibold">Add your first banner</a>
                        @endif
                    </p>
                </div>
            @else
                <ul x-ref="list" class="divide-y divide-slate-100">
                    @foreach ($sliders as $slider)
                        @php [$stateLabel, $stateClass] = $stateStyles[$slider->live_state]; @endphp
                        <li data-id="{{ $slider->id }}"
                            @if ($canReorder)
                                draggable="true"
                            @dragstart="start($event, $el)"
                            @dragover="over($event, $el)"
                            @dragend="end($el)"
                            @endif
                            class="flex items-center gap-4 px-4 py-3 hover:bg-slate-50/60 transition-colors">

                            @if ($canReorder)
                                <span class="cursor-grab active:cursor-grabbing text-slate-300 hover:text-slate-500 shrink-0" title="Drag to reorder" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/></svg>
                            </span>
                            @endif

                            <img src="{{ $slider->image_url }}" draggable="false" alt="{{ $slider->title }}"
                                 class="w-36 h-16 rounded-xl object-cover border border-slate-200 bg-slate-100 shrink-0">

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ $slider->title }}</p>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $stateClass }}" data-state-badge>{{ $stateLabel }}</span>
                                    @unless ($slider->show_text)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-500">Image only</span>
                                    @endunless
                                </div>
                                @if ($slider->subtitle)
                                    <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $slider->subtitle }}</p>
                                @endif
                                <p class="text-[11px] text-slate-400 mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                    @unless ($position)
                                        <span class="font-semibold text-slate-600">{{ $slider->position_label }}</span>
                                    @endunless
                                    <span class="truncate max-w-[16rem]">{{ $slider->target_label }}</span>
                                    <span>{{ $slider->schedule_label }}</span>
                                    <span>Order {{ $slider->serial_order }}</span>
                                </p>
                            </div>

                            {{-- Status toggle --}}
                            <div x-data="{ on: {{ $slider->status ? 'true' : 'false' }}, busy: false }" class="shrink-0">
                                <label class="relative inline-flex items-center cursor-pointer" title="Enable or disable this banner">
                                    <input type="checkbox" class="sr-only peer" :checked="on" :disabled="busy"
                                           @change="busy = true; toggle({{ $slider->id }}, $event.target).then(v => { on = v; busy = false })">
                                    <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                                </label>
                            </div>

                            <div class="flex items-center gap-1 shrink-0 text-xs font-medium">
                                <a href="{{ route('admin.sliders.edit', $slider) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</a>

                                <form id="delete-slider-{{ $slider->id }}" action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete('delete-slider-{{ $slider->id }}', 'This banner and its images will be permanently removed.')"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <script>
        function sliderList(cfg) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const toast = (icon, text) => {
                if (typeof Swal === 'undefined') return;
                Swal.fire({ icon, text, toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
            };

            return {
                dragging: null,

                /* ---------- drag to reorder ---------- */
                start(e, el) {
                    if (!cfg.canReorder) return;
                    this.dragging = el;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', el.dataset.id); // Firefox needs data to start a drag
                    el.classList.add('opacity-50');
                },
                over(e, el) {
                    if (!cfg.canReorder || !this.dragging || this.dragging === el) return;
                    e.preventDefault();
                    const box = el.getBoundingClientRect();
                    const after = e.clientY > box.top + box.height / 2;
                    el.parentNode.insertBefore(this.dragging, after ? el.nextSibling : el);
                },
                end(el) {
                    el.classList.remove('opacity-50');
                    if (!this.dragging) return;
                    this.dragging = null;
                    this.saveOrder();
                },
                saveOrder() {
                    const order = Array.from(this.$refs.list.children).map(li => Number(li.dataset.id));

                    fetch(cfg.reorderUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order }),
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || !data.success) throw new Error(data.message || `Request failed (HTTP ${res.status})`);
                            // keep the visible "Order N" labels in sync
                            Array.from(this.$refs.list.children).forEach((li, i) => {
                                const label = Array.from(li.querySelectorAll('span')).find(s => /^Order \d+$/.test(s.textContent.trim()));
                                if (label) label.textContent = `Order ${i + 1}`;
                            });
                            toast('success', data.message);
                        })
                        .catch(err => toast('error', err.message));
                },

                /* ---------- enable / disable ---------- */
                toggle(id, checkbox) {
                    const wanted = checkbox.checked;

                    return fetch(`{{ url('admin/sliders') }}/${id}/toggle`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || !data.success) throw new Error(data.message || `Request failed (HTTP ${res.status})`);

                            const badge = checkbox.closest('li').querySelector('[data-state-badge]');
                            if (badge) {
                                const states = {
                                    live: ['Live', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                                    scheduled: ['Scheduled', 'bg-sky-50 text-sky-700 border-sky-200'],
                                    expired: ['Expired', 'bg-amber-50 text-amber-700 border-amber-200'],
                                    inactive: ['Inactive', 'bg-slate-100 text-slate-600 border-slate-200'],
                                };
                                const [label, classes] = states[data.state] || states.inactive;
                                badge.textContent = label;
                                badge.className = `px-2 py-0.5 rounded-md text-[10px] font-bold border ${classes}`;
                            }
                            toast('success', data.message);
                            return data.status;
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
