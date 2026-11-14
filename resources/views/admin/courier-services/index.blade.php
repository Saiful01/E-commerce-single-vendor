@extends('layouts.admin')

@section('title', 'Courier Services')

@section('content')
    @php
        $reopen = ($errors->any() || session()->hasOldInput()) ? (old('_edit_id') ?: 'create') : null;

        $cfg = [
            'items'    => $items,
            'drivers'  => $drivers,
            'reopen'   => $reopen,
            'old'      => $reopen ? session()->getOldInput() : null,
            'storeUrl' => route('admin.courier-services.store'),
            'baseUrl'  => url('admin/courier-services'),
        ];

        $input  = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
        $label  = 'block text-xs font-semibold text-slate-700 mb-1';
        $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
    @endphp

    <div class="max-w-6xl mx-auto space-y-5" x-data="courierPage({{ \Illuminate\Support\Js::from($cfg) }})" @keydown.escape.window="closeModal()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Courier services</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $couriers->count() }} {{ Str::plural('courier', $couriers->count()) }} · {{ $couriers->where('is_active', true)->count() }} switched on</p>
            </div>
            <button type="button" @click="openCreate()"
                    class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add courier
            </button>
        </div>

        {{-- List --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Courier</th>
                        <th class="py-3.5 px-6">Contact</th>
                        <th class="py-3.5 px-6">Delivery time</th>
                        <th class="py-3.5 px-6">Setup</th>
                        <th class="py-3.5 px-6 text-center">Active</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($couriers as $courier)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-3">
                                    @if ($courier->logo_url)
                                        <img src="{{ $courier->logo_url }}" class="w-11 h-11 rounded-xl object-contain border border-slate-200 bg-white p-1 shrink-0" alt="{{ $courier->name }}">
                                    @else
                                        <span class="w-11 h-11 rounded-xl bg-slate-100 border border-slate-200 grid place-items-center text-slate-500 font-bold uppercase shrink-0">{{ mb_substr($courier->name, 0, 1) }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-800 truncate">
                                            {{ $courier->name }}
                                            @if ($courier->is_default) <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-600 text-white align-middle">Default</span> @endif
                                        </p>
                                        <p class="text-[11px] text-slate-400">{{ $courier->driver_label }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-6 text-slate-600">
                                @if ($courier->phone) <p>{{ $courier->phone }}</p> @endif
                                @if ($courier->email) <p class="text-[11px] text-slate-400">{{ $courier->email }}</p> @endif
                                @if (! $courier->phone && ! $courier->email) <span class="text-slate-400">Not set</span> @endif
                            </td>

                            <td class="py-3.5 px-6 text-slate-600">{{ $courier->delivery_time ?: 'Not set' }}</td>

                            <td class="py-3.5 px-6">
                                @if ($courier->driver === 'manual')
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Manual booking</span>
                                @elseif ($courier->isConfigured())
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">API ready</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Needs credentials</span>
                                @endif
                                @if ($courier->sandbox_mode) <span class="ml-1 px-2 py-1 rounded-md text-[10px] font-bold bg-violet-50 text-violet-700 border border-violet-200">Sandbox</span> @endif
                            </td>

                            <td class="py-3.5 px-6 text-center">
                                <div x-data="{ on: {{ $courier->is_active ? 'true' : 'false' }}, busy: false }" class="inline-block">
                                    <label class="relative inline-flex items-center cursor-pointer" title="Switch this courier on or off">
                                        <input type="checkbox" class="sr-only peer" :checked="on" :disabled="busy" aria-label="Active: {{ $courier->name }}"
                                               @change="busy = true; toggle({{ $courier->id }}, $event.target).then(v => { on = v; busy = false })">
                                        <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                                    </label>
                                </div>
                            </td>

                            <td class="py-3.5 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="openEdit({{ $courier->id }})" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</button>

                                    <form id="delete-courier-{{ $courier->id }}" action="{{ route('admin.courier-services.destroy', $courier) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="confirmDelete('delete-courier-{{ $courier->id }}', 'A courier that has been used on orders cannot be deleted. Switch it off instead.')"
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                No couriers yet. <button type="button" @click="openCreate()" class="text-indigo-600 font-semibold">Add the first one</button>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===================== ADD / EDIT MODAL ===================== --}}
        <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="editId ? 'Edit courier' : 'Add courier'">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" x-show="modal" x-transition.opacity @click="closeModal()"></div>

            <form :action="action" method="POST" enctype="multipart/form-data" x-show="modal"
                  x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                  class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl">
                @csrf
                <input type="hidden" name="_method" value="PUT" :disabled="!editId">
                <input type="hidden" name="_edit_id" :value="editId">
                <input type="hidden" name="remove_logo" :value="removeLogo ? 1 : 0">

                <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-800" x-text="editId ? 'Edit courier' : 'Add courier'"></h3>
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
                                <label class="{{ $label }}">Name *</label>
                                <input type="text" name="name" x-model="form.name" maxlength="100" placeholder="e.g. Steadfast Courier" class="{{ $input }}">
                            </div>
                            <div>
                                <label class="{{ $label }}">Integration *</label>
                                <select name="driver" x-model="form.driver" @change="form.credentials = {}" class="{{ $input }}">
                                    @foreach ($drivers as $key => $driver)
                                        <option value="{{ $key }}">{{ $driver['label'] }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[10px] text-slate-400 mt-1">Pick "Manual" if you book parcels yourself without an API.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="{{ $label }}">Phone</label>
                            <input type="text" name="phone" x-model="form.phone" maxlength="30" placeholder="+880 1XXX-XXXXXX" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Email</label>
                            <input type="email" name="email" x-model="form.email" maxlength="100" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Website</label>
                            <input type="url" name="website" x-model="form.website" maxlength="255" placeholder="https://" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="{{ $label }}">Delivery time</label>
                            <input type="text" name="delivery_time" x-model="form.delivery_time" maxlength="60" placeholder="2-3 days" class="{{ $input }}">
                        </div>
                    </div>

                    <div>
                        <label class="{{ $label }}">Tracking link</label>
                        <input type="url" name="tracking_url" x-model="form.tracking_url" maxlength="255" placeholder="https://steadfast.com.bd/t/{tracking_code}" class="{{ $input }} font-mono">
                        <p class="text-[10px] text-slate-400 mt-1">Write <span class="font-mono">{tracking_code}</span> where the consignment number goes. Customers get this link on their order.</p>
                    </div>

                    <div>
                        <label class="{{ $label }}">Coverage notes</label>
                        <textarea name="coverage_note" x-model="form.coverage_note" rows="2" maxlength="1000" placeholder="Areas covered, cash on delivery limits ..." class="{{ $input }}"></textarea>
                    </div>

                    {{-- Credentials --}}
                    <div x-show="fields.length > 0" x-cloak class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                        <div>
                            <p class="text-xs font-bold text-slate-800">API credentials</p>
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

                    {{-- Switches --}}
                    <div class="space-y-3 pt-1">
                        <label class="flex items-center justify-between gap-4 cursor-pointer" x-show="fields.length > 0" x-cloak>
                        <span>
                            <span class="block text-xs font-medium text-slate-700">Sandbox mode</span>
                            <span class="block text-[11px] text-slate-500">Use the courier's test servers, no real parcels are booked.</span>
                        </span>
                            <span class="relative inline-flex items-center shrink-0">
                            <input type="checkbox" name="sandbox_mode" value="1" x-model="form.sandbox_mode" class="sr-only peer">
                            <span class="{{ $toggle }}"></span>
                        </span>
                        </label>

                        <label class="flex items-center justify-between gap-4 cursor-pointer">
                        <span>
                            <span class="block text-xs font-medium text-slate-700">Default courier</span>
                            <span class="block text-[11px] text-slate-500">Pre-selected when you ship an order.</span>
                        </span>
                            <span class="relative inline-flex items-center shrink-0">
                            <input type="checkbox" name="is_default" value="1" x-model="form.is_default" class="sr-only peer">
                            <span class="{{ $toggle }}"></span>
                        </span>
                        </label>

                        <label class="flex items-center justify-between gap-4 cursor-pointer">
                        <span>
                            <span class="block text-xs font-medium text-slate-700">Active</span>
                            <span class="block text-[11px] text-slate-500">Inactive couriers cannot be picked on an order.</span>
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
                            x-text="editId ? 'Save changes' : 'Add courier'"></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function courierPage(cfg) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const toast = (icon, text) => {
                if (typeof Swal === 'undefined') return;
                Swal.fire({ icon, text, toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
            };
            const blank = () => ({
                name: '', driver: 'manual', phone: '', email: '', website: '', tracking_url: '',
                delivery_time: '', coverage_note: '', sandbox_mode: false, is_default: false, is_active: true,
                credentials: {},
            });

            return {
                items: cfg.items,
                drivers: cfg.drivers,
                modal: false,
                editId: null,
                form: blank(),
                logoPreview: null,
                removeLogo: false,
                originalLogo: null,

                get action() { return this.editId ? `${cfg.baseUrl}/${this.editId}` : cfg.storeUrl; },
                get fields() { return (this.drivers[this.form.driver] || { fields: [] }).fields; },
                get editing() { return this.items.find(i => i.id === this.editId) || null; },

                // a secret counts as saved only while the driver is still the one it was saved for
                isSaved(key) {
                    return !!this.editing && this.editing.driver === this.form.driver && this.editing.saved.includes(key);
                },

                init() {
                    if (cfg.reopen) {
                        const o = cfg.old || {};
                        this.editId = cfg.reopen === 'create' ? null : Number(cfg.reopen);
                        this.originalLogo = this.editing ? this.editing.logo : null;
                        this.logoPreview = this.originalLogo;
                        this.form = {
                            name: o.name ?? '', driver: o.driver || 'manual', phone: o.phone ?? '', email: o.email ?? '',
                            website: o.website ?? '', tracking_url: o.tracking_url ?? '', delivery_time: o.delivery_time ?? '',
                            coverage_note: o.coverage_note ?? '', sandbox_mode: !!o.sandbox_mode,
                            is_default: !!o.is_default, is_active: !!o.is_active,
                            credentials: {}, // secrets are never sent back to the page
                        };
                        this.modal = true;
                    }
                },

                openCreate() {
                    this.editId = null;
                    this.form = blank();
                    this.originalLogo = null;
                    this.logoPreview = null;
                    this.removeLogo = false;
                    this.modal = true;
                },
                openEdit(id) {
                    const c = this.items.find(i => i.id === id);
                    if (!c) return;
                    this.editId = id;
                    this.form = {
                        name: c.name, driver: c.driver, phone: c.phone || '', email: c.email || '', website: c.website || '',
                        tracking_url: c.tracking_url || '', delivery_time: c.delivery_time || '', coverage_note: c.coverage_note || '',
                        sandbox_mode: c.sandbox_mode, is_default: c.is_default, is_active: c.is_active,
                        credentials: { ...c.credentials },
                    };
                    this.originalLogo = c.logo;
                    this.logoPreview = c.logo;
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
