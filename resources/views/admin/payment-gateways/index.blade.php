@extends('layouts.admin')

@section('title', 'Payment Gateways')

@section('content')
    @php
        $reopen = ($errors->any() || session()->hasOldInput()) ? (old('_edit_id') ?: 'create') : null;
        $money  = \App\Models\SiteSetting::current();

        $cfg = [
            'items'      => $items,
            'drivers'    => $drivers,
            'reopen'     => $reopen,
            'old'        => $reopen ? session()->getOldInput() : null,
            'storeUrl'   => route('admin.payment-gateways.store'),
            'baseUrl'    => url('admin/payment-gateways'),
            'reorderUrl' => route('admin.payment-gateways.reorder'),
            'symbol'     => $money->currency_symbol,
        ];

        $typeStyles = [
            'offline' => 'bg-slate-100 text-slate-600 border-slate-200',
            'wallet'  => 'bg-pink-50 text-pink-700 border-pink-200',
            'online'  => 'bg-sky-50 text-sky-700 border-sky-200',
        ];

        $input  = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label  = 'block text-xs font-semibold text-slate-700 mb-1';
        $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
    @endphp

    <div class="max-w-5xl mx-auto space-y-5" x-data="gatewayPage({{ \Illuminate\Support\Js::from($cfg) }})" @keydown.escape.window="closeModal()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Payment methods</h2>
                <p class="text-xs text-slate-500 mt-1">How customers can pay. Drag the handle to set the order they see at checkout.</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add payment method
            </button>
        </div>

        @if ($gateways->isNotEmpty() && $gateways->where('is_active', true)->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[11px] text-amber-800">
                No payment method is switched on, so customers cannot check out yet.
            </div>
        @endif

        {{-- List --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            @if ($gateways->isEmpty())
                <div class="text-center py-14 px-6">
                    <p class="text-sm font-semibold text-slate-700">No payment methods yet</p>
                    <p class="text-xs text-slate-500 mt-1">Start with cash on delivery, it needs no setup.</p>
                    <button type="button" @click="openCreate()" class="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Add a payment method</button>
                </div>
            @else
                <ul x-ref="list" class="divide-y divide-slate-100">
                    @foreach ($gateways as $gateway)
                        <li data-id="{{ $gateway->id }}" draggable="true"
                            @dragstart="start($event, $el)" @dragover="over($event, $el)" @dragend="end($el)"
                            class="flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50/60 transition-colors">

                        <span class="cursor-grab active:cursor-grabbing text-slate-300 hover:text-slate-500 shrink-0" title="Drag to reorder" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 4a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM7 13a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm6 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"/></svg>
                        </span>

                            @if ($gateway->logo_url)
                                <img src="{{ $gateway->logo_url }}" draggable="false" class="w-11 h-11 rounded-xl object-contain border border-slate-200 bg-white p-1 shrink-0" alt="{{ $gateway->name }}">
                            @else
                                <span class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 grid place-items-center text-slate-500 font-bold uppercase shrink-0">{{ mb_substr($gateway->name, 0, 1) }}</span>
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ $gateway->name }}</p>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border {{ $typeStyles[$gateway->type] }}">{{ $types[$gateway->type] }}</span>
                                    @if ($gateway->isConfigured())
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Ready</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Needs setup</span>
                                    @endif
                                    @if ($gateway->sandbox_mode) <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200">Sandbox</span> @endif
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    {{ $gateway->driver_label }}
                                    <span class="mx-1">·</span>{{ $gateway->fee_label }}
                                    @if ($gateway->min_amount !== null || $gateway->max_amount !== null)
                                        <span class="mx-1">·</span>
                                        {{ $gateway->min_amount !== null ? $money->formatMoney($gateway->min_amount) : 'Any' }}
                                        to
                                        {{ $gateway->max_amount !== null ? $money->formatMoney($gateway->max_amount) : 'any amount' }}
                                    @endif
                                </p>
                            </div>

                            <div x-data="{ on: {{ $gateway->is_active ? 'true' : 'false' }}, busy: false }" class="shrink-0">
                                <label class="relative inline-flex items-center cursor-pointer" title="Switch this payment method on or off">
                                    <input type="checkbox" class="sr-only peer" :checked="on" :disabled="busy" aria-label="Active: {{ $gateway->name }}"
                                           @change="busy = true; toggle({{ $gateway->id }}, $event.target).then(v => { on = v; busy = false })">
                                    <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                                </label>
                            </div>

                            <div class="flex items-center gap-1 shrink-0 text-xs font-medium">
                                <button type="button" @click="openEdit({{ $gateway->id }})" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</button>

                                <form id="delete-gateway-{{ $gateway->id }}" action="{{ route('admin.payment-gateways.destroy', $gateway) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete('delete-gateway-{{ $gateway->id }}', 'A payment method that has been used on orders cannot be deleted. Switch it off instead.')"
                                            class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- ===================== ADD / EDIT MODAL ===================== --}}
        <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="editId ? 'Edit payment method' : 'Add payment method'">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" x-show="modal" x-transition.opacity @click="closeModal()"></div>

            <form :action="action" method="POST" enctype="multipart/form-data" x-show="modal"
                  x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                  class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!editId">
                <input type="hidden" name="_edit_id" :value="editId">
                <input type="hidden" name="remove_logo" :value="removeLogo ? 1 : 0">

                <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-800" x-text="editId ? 'Edit payment method' : 'Add payment method'"></h3>
                    <button type="button" @click="closeModal()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100" aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3">
                            <ul class="list-disc pl-5 text-[11px] text-rose-600 space-y-0.5">
                                @foreach ($errors->all() as $message) <li>{{ $message }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Basics --}}
                    <div class="flex gap-4">
                        <div class="shrink-0">
                            <label class="{{ $label }}">Logo</label>
                            <label class="block w-24 h-24 rounded-xl border-2 border-dashed border-slate-200 hover:border-indigo-400 bg-slate-50/50 cursor-pointer overflow-hidden grid place-items-center">
                                <img x-show="logoPreview" :src="logoPreview" class="w-full h-full object-contain p-1" alt="Logo preview">
                                <span x-show="!logoPreview" class="text-[10px] text-slate-400 text-center px-1">Click to upload</span>
                                <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="sr-only" @change="pickLogo($event)">
                            </label>
                            <button type="button" x-show="logoPreview" @click="clearLogo()" class="mt-1 text-[11px] font-semibold text-rose-600 hover:text-rose-800">Remove</button>
                        </div>

                        <div class="flex-1 space-y-4">
                            <div>
                                <label class="{{ $label }}">Type *</label>
                                <select name="driver" x-model="form.driver" @change="onDriverChange()" class="{{ $input }}">
                                    @foreach ($types as $typeKey => $typeName)
                                        <optgroup label="{{ $typeName }}">
                                            @foreach ($drivers as $key => $driver)
                                                @if ($driver['type'] === $typeKey)
                                                    <option value="{{ $key }}">{{ $driver['label'] }}</option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Name shown at checkout *</label>
                                <input type="text" name="name" x-model="form.name" maxlength="100" placeholder="e.g. bKash" class="{{ $input }}">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="{{ $label }}">Instructions for the customer</label>
                        <textarea name="instructions" x-model="form.instructions" rows="2" maxlength="500"
                                  placeholder="e.g. Send the money to the number below, then enter the transaction ID." class="{{ $input }}"></textarea>
                    </div>

                    {{-- Credentials / account details --}}
                    <div x-show="fields.length > 0" x-cloak class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                        <div>
                            <p class="text-xs font-bold text-slate-800" x-text="isOnline ? 'API credentials' : 'Account details'"></p>
                            <p class="text-[11px] text-slate-500">Stored encrypted. Saved secrets are never shown again. Leave a box empty to keep what is saved.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <template x-for="f in fields" :key="form.driver + f.key">
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-500" x-text="f.label"></label>
                                    <input :type="f.secret ? 'password' : 'text'" :name="`credentials[${f.key}]`" x-model="form.credentials[f.key]"
                                           :placeholder="isSaved(f.key) ? 'Saved. Leave empty to keep' : ''" autocomplete="off"
                                           class="w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Fee --}}
                    <div>
                        <p class="text-xs font-bold text-slate-800 mb-2">Extra fee</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="{{ $label }}">Fee type</label>
                                <select name="fee_type" x-model="form.fee_type" class="{{ $input }}">
                                    @foreach ($feeTypes as $key => $name)
                                        <option value="{{ $key }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="form.fee_type !== 'none'" x-cloak>
                                <label class="{{ $label }}" x-text="form.fee_type === 'percentage' ? 'Percentage' : 'Amount'"></label>
                                <div class="relative">
                                    <input type="number" name="fee_value" x-model="form.fee_value" step="0.01" min="0" :max="form.fee_type === 'percentage' ? 100 : null"
                                           :disabled="form.fee_type === 'none'" class="{{ $input }} pr-9">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="form.fee_type === 'percentage' ? '%' : symbol"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Limits --}}
                    <div>
                        <p class="text-xs font-bold text-slate-800 mb-2">Order total range <span class="font-normal text-slate-400">(optional)</span></p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="{{ $label }}">Minimum</label>
                                <div class="relative">
                                    <input type="number" name="min_amount" x-model="form.min_amount" step="0.01" min="0" placeholder="No minimum" class="{{ $input }} pr-9">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="symbol"></span>
                                </div>
                            </div>
                            <div>
                                <label class="{{ $label }}">Maximum</label>
                                <div class="relative">
                                    <input type="number" name="max_amount" x-model="form.max_amount" step="0.01" min="0" placeholder="No maximum" class="{{ $input }} pr-9">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="symbol"></span>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">For example, limit cash on delivery to orders under a certain amount.</p>
                    </div>

                    {{-- Switches --}}
                    <div class="space-y-3 pt-1">
                        <label class="flex items-center justify-between gap-4 cursor-pointer" x-show="isOnline" x-cloak>
                        <span>
                            <span class="block text-xs font-medium text-slate-700">Sandbox mode</span>
                            <span class="block text-[11px] text-slate-500">Use the gateway's test servers, no real money moves.</span>
                        </span>
                            <span class="relative inline-flex items-center shrink-0">
                            <input type="checkbox" name="sandbox_mode" value="1" x-model="form.sandbox_mode" class="sr-only peer">
                            <span class="{{ $toggle }}"></span>
                        </span>
                        </label>

                        <label class="flex items-center justify-between gap-4 cursor-pointer">
                        <span>
                            <span class="block text-xs font-medium text-slate-700">Active</span>
                            <span class="block text-[11px] text-slate-500">Shown at checkout. Needs every field above filled in.</span>
                        </span>
                            <span class="relative inline-flex items-center shrink-0">
                            <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="sr-only peer">
                            <span class="{{ $toggle }}"></span>
                        </span>
                        </label>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" @click="closeModal()" class="px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all"
                            x-text="editId ? 'Save changes' : 'Add payment method'"></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function gatewayPage(cfg) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const toast = (icon, text) => {
                if (typeof Swal === 'undefined') return;
                Swal.fire({ icon, text, toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
            };
            const blank = () => ({
                name: '', driver: 'cod', instructions: '', fee_type: 'none', fee_value: '',
                min_amount: '', max_amount: '', sandbox_mode: false, is_active: false, credentials: {},
            });

            return {
                items: cfg.items,
                drivers: cfg.drivers,
                symbol: cfg.symbol,
                modal: false,
                editId: null,
                form: blank(),
                logoPreview: null,
                removeLogo: false,
                originalLogo: null,
                dragging: null,

                get action() { return this.editId ? `${cfg.baseUrl}/${this.editId}` : cfg.storeUrl; },
                get driver() { return this.drivers[this.form.driver] || { label: '', type: 'offline', fields: [] }; },
                get fields() { return this.driver.fields; },
                get isOnline() { return this.driver.type !== 'offline'; },
                get editing() { return this.items.find(i => i.id === this.editId) || null; },

                // a secret counts as saved only while the type is still the one it was saved for
                isSaved(key) {
                    return !!this.editing && this.editing.driver === this.form.driver && this.editing.saved.includes(key);
                },

                // changing the type starts the credentials clean, and suggests a name for a new method
                onDriverChange() {
                    this.form.credentials = {};
                    if (!this.editId && (!this.form.name || this.nameWasSuggested)) {
                        this.form.name = this.driver.label.replace(/\s*\(.*\)$/, '');
                        this.nameWasSuggested = true;
                    }
                },
                nameWasSuggested: false,

                init() {
                    if (cfg.reopen) {
                        const o = cfg.old || {};
                        this.editId = cfg.reopen === 'create' ? null : Number(cfg.reopen);
                        this.originalLogo = this.editing ? this.editing.logo : null;
                        this.logoPreview = this.originalLogo;
                        this.form = {
                            name: o.name ?? '', driver: o.driver || 'cod', instructions: o.instructions ?? '',
                            fee_type: o.fee_type || 'none', fee_value: o.fee_value ?? '',
                            min_amount: o.min_amount ?? '', max_amount: o.max_amount ?? '',
                            sandbox_mode: !!o.sandbox_mode, is_active: !!o.is_active,
                            credentials: {}, // secrets are never sent back to the page
                        };
                        this.modal = true;
                    }
                },

                openCreate() {
                    this.editId = null;
                    this.form = blank();
                    this.form.name = 'Cash on delivery';
                    this.nameWasSuggested = true;
                    this.originalLogo = null;
                    this.logoPreview = null;
                    this.removeLogo = false;
                    this.modal = true;
                },
                openEdit(id) {
                    const g = this.items.find(i => i.id === id);
                    if (!g) return;
                    this.editId = id;
                    this.nameWasSuggested = false;
                    this.form = {
                        name: g.name, driver: g.driver, instructions: g.instructions || '',
                        fee_type: g.fee_type, fee_value: g.fee_type === 'none' ? '' : g.fee_value,
                        min_amount: g.min_amount ?? '', max_amount: g.max_amount ?? '',
                        sandbox_mode: g.sandbox_mode, is_active: g.is_active,
                        credentials: { ...g.credentials },
                    };
                    this.originalLogo = g.logo;
                    this.logoPreview = g.logo;
                    this.removeLogo = false;
                    this.modal = true;
                },
                closeModal() { this.modal = false; },

                pickLogo(e) {
                    const f = e.target.files[0];
                    if (!f) return;
                    this.logoPreview = URL.createObjectURL(f);
                    this.removeLogo = false;
                },
                clearLogo() {
                    document.querySelector('input[name=logo]').value = '';
                    this.logoPreview = null;
                    this.removeLogo = !!this.originalLogo;
                },

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
