{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $courier (null on create)
--}}
@php
    use App\Models\CourierService;

    $isEdit = ! is_null($courier);
    $hasOld = session()->hasOldInput();

    $driverMeta = collect(CourierService::DRIVERS)->map(fn ($d) => [
        'label' => $d['label'],
        'api'   => ! empty($d['fields']),
    ])->all();

    $cfg = [
        'name'        => old('name', $courier->name ?? ''),
        'driver'      => old('driver', $courier->driver ?? 'manual'),
        'phone'       => old('phone', $courier->phone ?? ''),
        'deliveryTime'=> old('delivery_time', $courier->delivery_time ?? ''),
        'trackingUrl' => old('tracking_url', $courier->tracking_url ?? ''),
        'logo'        => $courier->logo_url ?? null,
        'drivers'     => $driverMeta,
    ];

    $isActive  = $hasOld ? (bool) old('is_active') : ($courier->is_active ?? true);
    $isDefault = $hasOld ? (bool) old('is_default') : ($courier->is_default ?? false);
    $sandbox   = $hasOld ? (bool) old('sandbox_mode') : ($courier->sandbox_mode ?? true);
    $savedCreds = $isEdit ? ($courier->credentials ?? []) : [];

    $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label = 'block text-xs font-semibold text-slate-700 mb-1';
    $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err   = 'text-rose-500 text-[11px] mt-1 block';
    $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
    $toggleGreen = str_replace('peer-checked:bg-indigo-600', 'peer-checked:bg-emerald-500', $toggle);
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" x-data="courierForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-xs font-bold text-rose-700">Please fix the following before saving:</p>
            <ul class="mt-2 list-disc pl-5 text-[11px] text-rose-600 space-y-0.5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">

        {{-- ======================= MAIN COLUMN ======================= --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Basics --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Courier details</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Name *</label>
                        <input type="text" name="name" x-model="name" maxlength="100" placeholder="e.g. Steadfast Courier" class="{{ $input }}">
                        @error('name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Integration *</label>
                        <select name="driver" x-model="driver" class="{{ $input }}">
                            @foreach (CourierService::DRIVERS as $key => $d)
                                <option value="{{ $key }}">{{ $d['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Choose Manual if you book parcels yourself without an API.</p>
                        @error('driver') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Logo</label>
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0">
                            <img x-show="logoPreview" :src="logoPreview" alt="" class="w-full h-full object-contain" x-cloak>
                            <span x-show="!logoPreview" class="text-[10px] text-slate-400">No logo</span>
                        </div>
                        <div class="flex-1">
                            <input type="file" name="logo" accept="image/*" @change="pickLogo($event)"
                                   class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG or WebP.</p>
                        </div>
                    </div>
                    @if ($isEdit && $courier->logo)
                        <label class="inline-flex items-center gap-2 mt-2 text-[11px] text-slate-600 cursor-pointer">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Remove current logo
                        </label>
                    @endif
                    @error('logo') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="{{ $label }}">Phone</label>
                        <input type="text" name="phone" x-model="phone" maxlength="30" placeholder="01XXXXXXXXX" class="{{ $input }}">
                        @error('phone') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Email</label>
                        <input type="email" name="email" value="{{ old('email', $courier->email ?? '') }}" maxlength="150" placeholder="support@courier.com" class="{{ $input }}">
                        @error('email') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Website</label>
                        <input type="url" name="website" value="{{ old('website', $courier->website ?? '') }}" maxlength="255" placeholder="https://courier.com" class="{{ $input }}">
                        @error('website') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>

            {{-- Delivery info --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Delivery info</h3>
                    <p class="text-[11px] text-slate-500">Helps your team and customers know what to expect.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Delivery time</label>
                        <input type="text" name="delivery_time" x-model="deliveryTime" maxlength="100" placeholder="e.g. 1-3 days" class="{{ $input }}">
                        @error('delivery_time') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Tracking link</label>
                        <input type="text" name="tracking_url" x-model="trackingUrl" maxlength="255" placeholder="https://courier.com/track/{tracking_code}" class="{{ $input }} font-mono">
                        <p class="text-[10px] text-slate-400 mt-1">Use <code class="font-mono text-slate-600">{tracking_code}</code> where the code goes.</p>
                        @error('tracking_url') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Coverage note</label>
                    <textarea name="coverage_note" rows="3" maxlength="1000" placeholder="e.g. All 64 districts. Remote areas may take longer."
                              class="{{ $input }}">{{ old('coverage_note', $courier->coverage_note ?? '') }}</textarea>
                    @error('coverage_note') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- API credentials --}}
            <section class="{{ $card }}" x-show="isApi" x-cloak>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">API credentials</h3>
                    <p class="text-[11px] text-slate-500">Get these from your <span x-text="meta.label"></span> merchant account.</p>
                </div>

                @foreach (CourierService::DRIVERS as $driverKey => $d)
                    @continue(empty($d['fields']))
                    <div x-show="driver === '{{ $driverKey }}'" x-cloak>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($d['fields'] as $f)
                                @php
                                    $saved = ! empty($savedCreds[$f['key']]) && ($courier->driver ?? null) === $driverKey;
                                @endphp
                                <div>
                                    <label class="{{ $label }}">
                                        {{ $f['label'] }}
                                        @if ($f['secret'] && $saved)
                                            <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">Saved</span>
                                        @endif
                                    </label>
                                    @if ($f['secret'])
                                        <input type="password" name="credentials[{{ $f['key'] }}]" autocomplete="new-password"
                                               :disabled="driver !== '{{ $driverKey }}'"
                                               placeholder="{{ $saved ? 'Leave blank to keep the saved value' : 'Enter ' . strtolower($f['label']) }}"
                                               class="{{ $input }} font-mono">
                                    @else
                                        <input type="text" name="credentials[{{ $f['key'] }}]" autocomplete="off"
                                               :disabled="driver !== '{{ $driverKey }}'"
                                               value="{{ old('credentials.' . $f['key'], $savedCreds[$f['key']] ?? '') }}"
                                               class="{{ $input }}">
                                    @endif
                                    @error('credentials.' . $f['key']) <span class="{{ $err }}">{{ $message }}</span> @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <p class="text-[10px] text-slate-400">Secret values are stored encrypted and are never shown again after saving.</p>

                <label class="flex items-center justify-between cursor-pointer pt-1">
                    <span>
                        <span class="block text-xs font-semibold text-slate-700">Sandbox (test) mode</span>
                        <span class="block text-[11px] text-slate-500">Send test bookings only. Turn off to go live.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="sandbox_mode" value="1" @checked($sandbox) :disabled="!isApi" class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>
            </section>
        </div>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="space-y-4 lg:sticky lg:top-6">

            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Status</h3>

                <label class="flex items-center justify-between cursor-pointer">
                    <span>
                        <span class="block text-xs font-medium text-slate-700">Active</span>
                        <span class="block text-[11px] text-slate-500">Turn off to stop using it for new orders.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="is_active" value="1" @checked($isActive) class="sr-only peer">
                        <span class="{{ $toggleGreen }}"></span>
                    </span>
                </label>

                <label class="flex items-center justify-between cursor-pointer">
                    <span>
                        <span class="block text-xs font-medium text-slate-700">Default courier</span>
                        <span class="block text-[11px] text-slate-500">Pre-selected when you ship an order.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="is_default" value="1" @checked($isDefault) class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.courier-services.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Save courier' }}
                    </button>
                </div>
            </section>

            {{-- Live preview --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Preview</h3>

                <div class="rounded-xl border-2 border-dashed border-indigo-300 bg-indigo-50/40 p-4 space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-white border border-indigo-100 flex items-center justify-center overflow-hidden shrink-0">
                            <img x-show="logoPreview" :src="logoPreview" alt="" class="w-full h-full object-contain" x-cloak>
                            <span x-show="!logoPreview" class="text-[10px] font-bold text-indigo-300" x-text="(name || '?').charAt(0).toUpperCase()"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 truncate" x-text="name || 'Courier name'"></p>
                            <p class="text-[11px] text-slate-500" x-text="meta.label"></p>
                        </div>
                    </div>
                    <ul class="text-[11px] text-slate-600 space-y-0.5">
                        <template x-for="line in details" :key="line">
                            <li x-text="line"></li>
                        </template>
                    </ul>
                </div>
                <p class="text-[10px] text-slate-400">Approximate. Shows how the courier looks in your lists.</p>
            </section>

            @if ($isEdit)
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">Results</h3>
                    <dl class="text-xs space-y-2">
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Orders shipped</dt><dd class="font-semibold text-slate-800">{{ $courier->ordersCount() }}</dd></div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Setup</dt>
                            <dd class="font-semibold {{ $courier->isConfigured() ? 'text-emerald-600' : 'text-amber-600' }}">{{ $courier->isConfigured() ? 'Complete' : 'Incomplete' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800">{{ $courier->created_at?->format('d M Y') }}</dd></div>
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</form>

<script>
    function courierForm(cfg) {
        return {
            name: cfg.name || '',
            driver: cfg.driver,
            phone: cfg.phone,
            deliveryTime: cfg.deliveryTime,
            trackingUrl: cfg.trackingUrl,
            logoPreview: cfg.logo,

            get meta() { return cfg.drivers[this.driver] || { label: this.driver, api: false }; },
            get isApi() { return !!this.meta.api; },

            pickLogo(e) {
                const file = e.target.files[0];
                this.logoPreview = file ? URL.createObjectURL(file) : cfg.logo;
            },

            get details() {
                const lines = [];
                lines.push(this.isApi ? 'Connected by API' : 'Booked manually');
                if (this.phone) lines.push(`Phone ${this.phone}`);
                if (this.deliveryTime) lines.push(`Delivers in ${this.deliveryTime}`);
                if (this.trackingUrl) lines.push('Tracking link set');
                return lines;
            },
        };
    }
</script>
