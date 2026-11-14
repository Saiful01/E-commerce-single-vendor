{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $gateway (null on create)
--}}
@php
    use App\Models\PaymentGateway;

    $isEdit = ! is_null($gateway);
    $hasOld = session()->hasOldInput();
    $money  = \App\Models\SiteSetting::current();

    // Per-driver info for the Alpine side (never includes credential values)
    $driverMeta = collect(PaymentGateway::DRIVERS)->map(fn ($d) => [
        'label'   => $d['label'],
        'type'    => $d['type'],
        'sandbox' => collect($d['fields'])->contains('secret', true),
    ])->all();

    $cfg = [
        'name'     => old('name', $gateway->name ?? ''),
        'driver'   => old('driver', $gateway->driver ?? 'cod'),
        'feeType'  => old('fee_type', $gateway->fee_type ?? 'none'),
        'feeValue' => old('fee_value', ($gateway->fee_value ?? 0) ?: ''),
        'min'      => old('min_amount', $gateway->min_amount ?? ''),
        'max'      => old('max_amount', $gateway->max_amount ?? ''),
        'logo'     => $gateway->logo_url ?? null,
        'drivers'  => $driverMeta,
        'types'    => PaymentGateway::TYPES,
        'symbol'   => $money->currency_symbol,
        'position' => $money->currency_position,
    ];

    $isActive = $hasOld ? (bool) old('is_active') : ($gateway->is_active ?? true);
    $sandbox  = $hasOld ? (bool) old('sandbox_mode') : ($gateway->sandbox_mode ?? true);
    $savedCreds = $isEdit ? ($gateway->credentials ?? []) : [];

    $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label = 'block text-xs font-semibold text-slate-700 mb-1';
    $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err   = 'text-rose-500 text-[11px] mt-1 block';
    $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-600 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
    $toggleGreen = str_replace('peer-checked:bg-indigo-600', 'peer-checked:bg-emerald-500', $toggle);
    $choice = 'block p-3 rounded-xl border border-slate-200 bg-white text-left transition-colors hover:border-indigo-200 peer-checked:border-indigo-500 peer-checked:bg-indigo-50/60 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-400';
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" x-data="gatewayForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
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
                <h3 class="text-sm font-bold text-slate-800">Payment method</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Name *</label>
                        <input type="text" name="name" x-model="name" maxlength="100" placeholder="e.g. bKash" class="{{ $input }}">
                        <p class="text-[10px] text-slate-400 mt-1">Customers see this at checkout.</p>
                        @error('name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Type *</label>
                        <select name="driver" x-model="driver" class="{{ $input }}">
                            @foreach (collect(PaymentGateway::DRIVERS)->groupBy('type') as $type => $group)
                                <optgroup label="{{ PaymentGateway::TYPES[$type] ?? ucfirst($type) }}">
                                    @foreach (PaymentGateway::DRIVERS as $key => $d)
                                        @if ($d['type'] === $type)
                                            <option value="{{ $key }}">{{ $d['label'] }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-slate-400 mt-1">Decides which details you need to fill in below.</p>
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
                            <p class="text-[10px] text-slate-400 mt-1">PNG, JPG or WebP. Shown next to the name at checkout.</p>
                        </div>
                    </div>
                    @if ($isEdit && $gateway->logo)
                        <label class="inline-flex items-center gap-2 mt-2 text-[11px] text-slate-600 cursor-pointer">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            Remove current logo
                        </label>
                    @endif
                    @error('logo') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="{{ $label }}">Instructions for the customer</label>
                    <textarea name="instructions" rows="3" maxlength="2000" placeholder="e.g. Send the money to the number below, then enter the transaction ID at checkout."
                              class="{{ $input }}">{{ old('instructions', $gateway->instructions ?? '') }}</textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Shown when the customer picks this method.</p>
                    @error('instructions') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- Credentials / account details --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Account details</h3>
                    <p class="text-[11px] text-slate-500" x-text="isOffline ? 'What customers need to pay you.' : 'Keys and numbers for this method.'"></p>
                </div>

                @foreach (PaymentGateway::DRIVERS as $driverKey => $d)
                    <div x-show="driver === '{{ $driverKey }}'" x-cloak>
                        @if (empty($d['fields']))
                            <p class="text-xs text-slate-500 py-3 text-center border border-dashed border-slate-200 rounded-xl">Nothing to set up for {{ $d['label'] }}.</p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($d['fields'] as $f)
                                    @php
                                        $saved = ! empty($savedCreds[$f['key']]) && ($gateway->driver ?? null) === $driverKey;
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
                        @endif
                    </div>
                @endforeach

                <p class="text-[10px] text-slate-400" x-show="hasSecrets" x-cloak>Secret values are stored encrypted and are never shown again after saving.</p>

                <label class="flex items-center justify-between cursor-pointer pt-1" x-show="canSandbox" x-cloak>
                    <span>
                        <span class="block text-xs font-semibold text-slate-700">Sandbox (test) mode</span>
                        <span class="block text-[11px] text-slate-500">Use test keys. No real money moves. Turn off to go live.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="sandbox_mode" value="1" @checked($sandbox) :disabled="!canSandbox" class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>
            </section>

            {{-- Fee --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Extra fee</h3>
                    <p class="text-[11px] text-slate-500">Optional charge added to the order when the customer picks this method.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5" role="radiogroup" aria-label="Fee type">
                    @foreach (PaymentGateway::FEE_TYPES as $value => $name)
                        <label class="cursor-pointer">
                            <input type="radio" name="fee_type" value="{{ $value }}" x-model="feeType" class="sr-only peer">
                            <span class="{{ $choice }}">
                                <span class="block text-xs font-bold text-slate-800">{{ $value === 'none' ? 'No fee' : ($value === 'percentage' ? 'Percentage' : 'Fixed amount') }}</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5">{{ $name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('fee_type') <span class="{{ $err }}">{{ $message }}</span> @enderror

                <div x-show="feeType !== 'none'" x-cloak class="max-w-xs">
                    <label class="{{ $label }}"><span x-text="feeType === 'percentage' ? 'Fee percentage *' : 'Fee amount *'"></span></label>
                    <div class="relative">
                        <input type="number" name="fee_value" x-model="feeValue" step="0.01" min="0" :max="feeType === 'percentage' ? 100 : null"
                               :disabled="feeType === 'none'" class="{{ $input }} pr-9">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="feeType === 'percentage' ? '%' : symbol"></span>
                    </div>
                    @error('fee_value') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- Order limits --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Order limits</h3>
                    <p class="text-[11px] text-slate-500">The method is hidden at checkout when the order total is outside this range. Leave empty for no limit.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Minimum order</label>
                        <div class="relative">
                            <input type="number" name="min_amount" x-model="min" step="0.01" min="0" placeholder="No minimum" class="{{ $input }} pr-9">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="symbol"></span>
                        </div>
                        @error('min_amount') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Maximum order</label>
                        <div class="relative">
                            <input type="number" name="max_amount" x-model="max" step="0.01" min="0" placeholder="No maximum" class="{{ $input }} pr-9">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" x-text="symbol"></span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Handy for cash on delivery on big orders.</p>
                        @error('max_amount') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>
            </section>
        </div>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="space-y-4 lg:sticky lg:top-6">

            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Status</h3>

                <label class="flex items-center justify-between cursor-pointer">
                    <span>
                        <span class="block text-xs font-medium text-slate-700">Active</span>
                        <span class="block text-[11px] text-slate-500">Turn off to hide it at checkout.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="is_active" value="1" @checked($isActive) class="sr-only peer">
                        <span class="{{ $toggleGreen }}"></span>
                    </span>
                </label>

                <div>
                    <label class="{{ $label }}">Sort order</label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $gateway->sort_order ?? 0) }}" class="{{ $input }}">
                    <p class="text-[10px] text-slate-400 mt-1">Smaller numbers show first.</p>
                    @error('sort_order') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.payment-gateways.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Save method' }}
                    </button>
                </div>
            </section>

            {{-- Live preview --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Checkout preview</h3>

                <div class="rounded-xl border-2 border-dashed border-indigo-300 bg-indigo-50/40 p-4 space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-white border border-indigo-100 flex items-center justify-center overflow-hidden shrink-0">
                            <img x-show="logoPreview" :src="logoPreview" alt="" class="w-full h-full object-contain" x-cloak>
                            <span x-show="!logoPreview" class="text-[10px] font-bold text-indigo-300" x-text="(name || '?').charAt(0).toUpperCase()"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 truncate" x-text="name || 'Payment method'"></p>
                            <p class="text-[11px] text-slate-500" x-text="meta.label + ' · ' + (types[meta.type] || '')"></p>
                        </div>
                    </div>
                    <ul class="text-[11px] text-slate-600 space-y-0.5">
                        <template x-for="line in details" :key="line">
                            <li x-text="line"></li>
                        </template>
                    </ul>
                </div>
                <p class="text-[10px] text-slate-400">Approximate. Shows how the method reads to customers.</p>
            </section>

            @if ($isEdit)
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">Results</h3>
                    <dl class="text-xs space-y-2">
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Orders paid</dt><dd class="font-semibold text-slate-800">{{ $gateway->ordersCount() }}</dd></div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Setup</dt>
                            <dd class="font-semibold {{ $gateway->isConfigured() ? 'text-emerald-600' : 'text-amber-600' }}">{{ $gateway->isConfigured() ? 'Complete' : 'Incomplete' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800">{{ $gateway->created_at?->format('d M Y') }}</dd></div>
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</form>

<script>
    function gatewayForm(cfg) {
        return {
            name: cfg.name || '',
            driver: cfg.driver,
            feeType: cfg.feeType,
            feeValue: cfg.feeValue,
            min: cfg.min,
            max: cfg.max,
            symbol: cfg.symbol,
            types: cfg.types,
            logoPreview: cfg.logo,

            get meta() { return cfg.drivers[this.driver] || { label: this.driver, type: 'offline', sandbox: false }; },
            get isOffline() { return this.meta.type === 'offline'; },
            get canSandbox() { return !!this.meta.sandbox; },
            get hasSecrets() { return !!this.meta.sandbox; },

            pickLogo(e) {
                const file = e.target.files[0];
                this.logoPreview = file ? URL.createObjectURL(file) : cfg.logo;
            },

            money(n) {
                const num = Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                return cfg.position === 'after' ? `${num} ${cfg.symbol}` : `${cfg.symbol}${num}`;
            },
            get details() {
                const lines = [];
                if (this.feeType === 'percentage' && Number(this.feeValue)) lines.push(`${Number(this.feeValue)}% fee`);
                else if (this.feeType === 'fixed' && Number(this.feeValue)) lines.push(`${this.money(this.feeValue)} fee`);
                else lines.push('No fee');
                if (Number(this.min)) lines.push(`Orders from ${this.money(this.min)}`);
                if (Number(this.max)) lines.push(`Orders up to ${this.money(this.max)}`);
                return lines;
            },
        };
    }
</script>
