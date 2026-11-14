@extends('layouts.admin')

@section('title', 'Order Statuses')

@section('content')
    @php
        $reopen = ($errors->any() || session()->hasOldInput()) ? (old('_edit_id') ?: 'create') : null;

        $cfg = [
            'items'     => $items,
            'reopen'    => $reopen,
            'old'       => $reopen ? session()->getOldInput() : null,
            'storeUrl'  => route('admin.order-statuses.store'),
            'baseUrl'   => url('admin/order-statuses'),
            'reorderUrl'=> route('admin.order-statuses.reorder'),
        ];

        $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label = 'block text-xs font-semibold text-slate-700 mb-1';
        $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
    @endphp

    <div class="max-w-5xl mx-auto space-y-5" x-data="statusPage({{ \Illuminate\Support\Js::from($cfg) }})" @keydown.escape.window="closeModal()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Order statuses</h2>
                <p class="text-xs text-slate-500 mt-1">The steps an order goes through, in order. Drag the handle to rearrange.</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add status
            </button>
        </div>

        {{-- List --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            @if ($statuses->isEmpty())
                <div class="text-center py-14 px-6">
                    <p class="text-sm font-semibold text-slate-700">No statuses yet</p>
                    <p class="text-xs text-slate-500 mt-1">Load the standard set by running:</p>
                    <code class="inline-block mt-2 px-3 py-1.5 rounded-lg bg-slate-900 text-slate-100 text-[11px]">php artisan db:seed --class=OrderStatusSeeder</code>
                </div>
            @else
                <ul x-ref="list" class="divide-y divide-slate-100">
                    @foreach ($statuses as $status)
                        <li data-id="{{ $status->id }}" draggable="true"
                            @dragstart="start($event, $el)" @dragover="over($event, $el)" @dragend="end($el)"
                            class="flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50/60 transition-colors">

                        <span class="cursor-grab active:cursor-grabbing text-slate-300 hover:text-slate-500 shrink-0" title="Drag to reorder" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/></svg>
                        </span>

                            <span class="shrink-0 px-3 py-1 rounded-full text-[11px] font-bold border min-w-24 text-center"
                                  style="background-color: {{ $status->color }}1a; color: {{ $status->color }}; border-color: {{ $status->color }}55">{{ $status->name }}</span>

                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-slate-600 truncate">{{ $status->description ?: 'No description' }}</p>
                                <p class="mt-1 flex flex-wrap gap-1.5">
                                    @if ($status->is_default) <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-600 text-white">Default</span> @endif
                                    @if ($status->is_final) <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600">Final</span> @endif
                                    @if ($status->restock_items) <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700">Restocks items</span> @endif
                                    @if ($status->notify_customer) <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sky-50 text-sky-700">Notifies customer</span> @endif
                                    @if ($status->is_system) <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-500">Built in</span> @endif
                                </p>
                            </div>

                            <div x-data="{ on: {{ $status->is_active ? 'true' : 'false' }}, busy: false }" class="shrink-0">
                                <label class="relative inline-flex items-center cursor-pointer" title="Switch this status on or off">
                                    <input type="checkbox" class="sr-only peer" :checked="on" :disabled="busy" aria-label="Active: {{ $status->name }}"
                                           @change="busy = true; toggle({{ $status->id }}, $event.target).then(v => { on = v; busy = false })">
                                    <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                                </label>
                            </div>

                            <div class="flex items-center gap-1 shrink-0 text-xs font-medium">
                                <button type="button" @click="openEdit({{ $status->id }})" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</button>

                                @unless ($status->is_system || $status->is_default)
                                    <form id="delete-status-{{ $status->id }}" action="{{ route('admin.order-statuses.destroy', $status) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="confirmDelete('delete-status-{{ $status->id }}', 'A status that orders still use cannot be deleted.')"
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                    </form>
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- ===================== ADD / EDIT MODAL ===================== --}}
        <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="editId ? 'Edit status' : 'Add status'">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" x-show="modal" x-transition.opacity @click="closeModal()"></div>

            <form :action="action" method="POST" x-show="modal"
                  x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                  class="relative bg-white w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!editId">
                <input type="hidden" name="_edit_id" :value="editId">

                <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-800" x-text="editId ? 'Edit status' : 'Add status'"></h3>
                    <button type="button" @click="closeModal()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3">
                            <ul class="list-disc pl-5 text-[11px] text-rose-600 space-y-0.5">
                                @foreach ($errors->all() as $message) <li>{{ $message }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label class="{{ $label }}">Name *</label>
                        <input type="text" name="name" x-model="form.name" maxlength="60" placeholder="e.g. Out for delivery" class="{{ $input }}">
                    </div>

                    <div>
                        <label class="{{ $label }}">Color *</label>
                        <div class="flex items-center gap-2">
                            <input type="color" x-model="form.color" class="h-9 w-12 shrink-0 rounded-lg border border-slate-200 p-1 cursor-pointer bg-white" aria-label="Color picker">
                            <input type="text" name="color" x-model="form.color" maxlength="7" class="{{ $input }} font-mono uppercase">
                            <span class="shrink-0 px-3 py-1 rounded-full text-[11px] font-bold border"
                                  :style="{ backgroundColor: form.color + '1a', color: form.color, borderColor: form.color + '55' }"
                                  x-text="form.name || 'Preview'"></span>
                        </div>
                    </div>

                    <div>
                        <label class="{{ $label }}">Description</label>
                        <input type="text" name="description" x-model="form.description" maxlength="255" placeholder="What this step means" class="{{ $input }}">
                    </div>

                    <div class="space-y-3 pt-1">
                        @foreach ([
                            ['is_default',      'Default for new orders',  'Every new order starts with this status.'],
                            ['is_final',        'Final status',            'The order cannot move on from here (delivered, cancelled).'],
                            ['restock_items',   'Put items back in stock', 'Use for cancelled and returned.'],
                            ['notify_customer', 'Notify the customer',     'Send an SMS or email when an order reaches this status.'],
                            ['is_active',       'Active',                  'Inactive statuses cannot be picked on an order.'],
                        ] as [$field, $title, $hint])
                            <label class="flex items-center justify-between gap-4 cursor-pointer">
                            <span>
                                <span class="block text-xs font-medium text-slate-700">{{ $title }}</span>
                                <span class="block text-[11px] text-slate-500">{{ $hint }}</span>
                            </span>
                                <span class="relative inline-flex items-center shrink-0">
                                <input type="checkbox" name="{{ $field }}" value="1" x-model="form.{{ $field }}" class="sr-only peer">
                                <span class="{{ $toggle }}"></span>
                            </span>
                            </label>
                        @endforeach
                    </div>

                    <p class="text-[11px] text-slate-400" x-show="editId && form.slug">Internal code: <span class="font-mono" x-text="form.slug"></span> (cannot be changed)</p>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="closeModal()" class="px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all"
                            x-text="editId ? 'Save changes' : 'Add status'"></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function statusPage(cfg) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const toast = (icon, text) => {
                if (typeof Swal === 'undefined') return;
                Swal.fire({ icon, text, toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
            };
            const blank = () => ({
                name: '', slug: '', color: '#64748B', description: '',
                is_default: false, is_final: false, restock_items: false, notify_customer: true, is_active: true,
            });

            return {
                items: cfg.items,
                modal: false,
                editId: null,
                form: blank(),
                dragging: null,

                get action() {
                    return this.editId ? `${cfg.baseUrl}/${this.editId}` : cfg.storeUrl;
                },

                init() {
                    // After a failed save, reopen the modal with what was typed
                    if (cfg.reopen) {
                        const o = cfg.old || {};
                        this.editId = cfg.reopen === 'create' ? null : Number(cfg.reopen);
                        const base = this.editId ? (this.items.find(i => i.id === this.editId) || {}) : {};
                        this.form = {
                            ...blank(), slug: base.slug || '',
                            name: o.name ?? '', color: o.color || '#64748B', description: o.description ?? '',
                            is_default: !!o.is_default, is_final: !!o.is_final, restock_items: !!o.restock_items,
                            notify_customer: !!o.notify_customer, is_active: !!o.is_active,
                        };
                        this.modal = true;
                    }
                },

                openCreate() { this.editId = null; this.form = blank(); this.modal = true; },
                openEdit(id) {
                    const s = this.items.find(i => i.id === id);
                    if (!s) return;
                    this.editId = id;
                    this.form = {
                        name: s.name, slug: s.slug, color: s.color, description: s.description || '',
                        is_default: s.is_default, is_final: s.is_final, restock_items: s.restock_items,
                        notify_customer: s.notify_customer, is_active: s.is_active,
                    };
                    this.modal = true;
                },
                closeModal() { this.modal = false; },

                /* ---------- drag to reorder ---------- */
                start(e, el) {
                    this.dragging = el;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', el.dataset.id); // Firefox needs data to start a drag
                    el.classList.add('opacity-50');
                },
                over(e, el) {
                    if (!this.dragging || this.dragging === el) return;
                    e.preventDefault();
                    const box = el.getBoundingClientRect();
                    el.parentNode.insertBefore(this.dragging, e.clientY > box.top + box.height / 2 ? el.nextSibling : el);
                },
                end(el) {
                    el.classList.remove('opacity-50');
                    if (!this.dragging) return;
                    this.dragging = null;

                    const order = Array.from(this.$refs.list.children).map(li => Number(li.dataset.id));
                    fetch(cfg.reorderUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order }),
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || !data.success) throw new Error(data.message || `Request failed (HTTP ${res.status})`);
                            toast('success', data.message);
                        })
                        .catch(err => toast('error', err.message));
                },

                /* ---------- on / off ---------- */
                toggle(id, checkbox) {
                    const wanted = checkbox.checked;

                    return fetch(`${cfg.baseUrl}/${id}/toggle`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || !data.success) throw new Error(data.message || `Request failed (HTTP ${res.status})`);
                            const item = this.items.find(i => i.id === id);
                            if (item) item.is_active = data.value;
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
