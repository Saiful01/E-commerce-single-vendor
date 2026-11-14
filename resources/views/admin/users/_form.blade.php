{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $user (null on create), $roles (names), $userRoles (names)
--}}
@php
    $isEdit  = ! is_null($user);
    $hasOld  = session()->hasOldInput();
    $isSelf  = $isEdit && $user->id === auth()->id();

    $initialAddresses = old('addresses');
    if (is_null($initialAddresses)) {
        $initialAddresses = $isEdit
            ? $user->addresses->sortByDesc('is_default')->map(fn ($a) => [
                'id'           => $a->id,
                'address_type' => $a->address_type,
                'full_name'    => $a->full_name,
                'phone'        => $a->phone,
                'city'         => $a->city,
                'area'         => $a->area,
                'address_line' => $a->address_line,
                'is_default'   => $a->is_default,
            ])->values()->all()
            : [];
    }

    $cfg = [
        'userType'  => old('user_type', $user->user_type ?? request('type', 'admin')),
        'name'      => old('name', $user->name ?? ''),
        'phone'     => old('phone', $user->phone ?? ''),
        'addresses' => array_map(fn ($a) => $a + ['id' => null, 'address_type' => 'shipping', 'is_default' => false], array_values($initialAddresses)),
    ];

    $selectedRoles = old('roles', $userRoles);
    $isActive      = $hasOld ? (bool) old('status') : ($user->status ?? true);

    $input  = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label  = 'block text-xs font-semibold text-slate-700 mb-1';
    $card   = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err    = 'text-rose-500 text-[11px] mt-1 block';
    $toggle = "w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4";
@endphp

<form action="{{ $action }}" method="POST" x-data="userForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
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

            {{-- Account --}}
            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Account details</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="{{ $label }}">Full name *</label>
                        <input type="text" name="name" x-model="name" maxlength="255" class="{{ $input }}" autocomplete="off">
                        @error('name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Phone</label>
                        <input type="text" name="phone" x-model="phone" maxlength="20" placeholder="+880 1XXX-XXXXXX" class="{{ $input }}" autocomplete="off">
                        @error('phone') <span class="{{ $err }}">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="{{ $label }}">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" maxlength="255" class="{{ $input }}" autocomplete="off">
                    @error('email') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- Security --}}
            <section class="{{ $card }}">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Password</h3>
                    @if ($isEdit)
                        <p class="text-[11px] text-slate-500">Leave blank to keep the current password.</p>
                    @endif
                </div>

                <div>
                    <label class="{{ $label }}">{{ $isEdit ? 'New password' : 'Password *' }}</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password" class="{{ $input }} pr-14" autocomplete="new-password">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-slate-500 hover:text-slate-800"
                                    x-text="showPassword ? 'Hide' : 'Show'"></button>
                        </div>
                        <button type="button" @click="generatePassword()" class="px-3 py-2 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 shrink-0">Generate</button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1" x-show="generated" x-cloak>Copy this password now. It cannot be shown again after saving.</p>
                    @error('password') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>
            </section>

            {{-- Roles (admin users only) --}}
            <section class="{{ $card }}" x-show="userType === 'admin'" x-cloak>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Roles</h3>
                    <p class="text-[11px] text-slate-500">Decides which parts of the admin panel this person can open.</p>
                </div>

                @if ($roles->isEmpty())
                    <p class="text-xs text-slate-500">No roles exist yet. Create one under Roles & Permissions first.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-200/80 text-xs font-medium cursor-pointer hover:bg-slate-50 has-[:checked]:border-indigo-300 has-[:checked]:bg-indigo-50/50">
                                <input type="checkbox" name="roles[]" value="{{ $role }}" :disabled="userType !== 'admin'"
                                       @checked(in_array($role, (array) $selectedRoles))
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $role }}
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('roles') <span class="{{ $err }}">{{ $message }}</span> @enderror
                @error('roles.*') <span class="{{ $err }}">{{ $message }}</span> @enderror
            </section>

            {{-- Addresses --}}
            <section class="{{ $card }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Addresses</h3>
                        <p class="text-[11px] text-slate-500">Shipping and billing addresses. Each type has one default.</p>
                    </div>
                    <button type="button" @click="addAddress()" x-show="addresses.length < 10"
                            class="px-3 py-1.5 bg-indigo-50 text-indigo-600 font-semibold text-xs rounded-lg hover:bg-indigo-100 shrink-0">Add address</button>
                </div>

                <div x-show="addresses.length === 0" class="rounded-xl border border-dashed border-slate-200 py-8 text-center">
                    <p class="text-xs text-slate-500">No addresses yet.</p>
                    <button type="button" @click="addAddress()" class="mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Add the first address</button>
                </div>

                <div class="space-y-3">
                    <template x-for="(a, index) in addresses" :key="a._key">
                        <div class="rounded-xl border bg-slate-50 p-4 space-y-3" :class="a.is_default ? 'border-indigo-300' : 'border-slate-200'">
                            <input type="hidden" :name="`addresses[${index}][id]`" :value="a.id" :disabled="!a.id">
                            <input type="hidden" :name="`addresses[${index}][is_default]`" :value="a.is_default ? 1 : 0">

                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <select :name="`addresses[${index}][address_type]`" x-model="a.address_type" @change="normalizeDefaults()"
                                            class="text-xs rounded-lg border-slate-200 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach (\App\Models\UserAddress::TYPES as $value => $name)
                                            <option value="{{ $value }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <span x-show="a.is_default" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-600 text-white">Default</span>
                                </div>
                                <div class="flex items-center gap-3 text-[11px] font-semibold">
                                    <button type="button" x-show="!a.is_default" @click="makeDefault(index)" class="text-indigo-600 hover:text-indigo-800">Make default</button>
                                    <button type="button" @click="fillFromAccount(a)" class="text-slate-500 hover:text-slate-800">Use account name and phone</button>
                                    <button type="button" @click="removeAddress(index)" class="text-rose-600 hover:text-rose-800">Remove</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-500">Full name</label>
                                    <input type="text" :name="`addresses[${index}][full_name]`" x-model="a.full_name" maxlength="100" class="w-full text-xs rounded-lg border-slate-200">
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-500">Phone</label>
                                    <input type="text" :name="`addresses[${index}][phone]`" x-model="a.phone" maxlength="20" class="w-full text-xs rounded-lg border-slate-200">
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-500">City / District</label>
                                    <input type="text" :name="`addresses[${index}][city]`" x-model="a.city" maxlength="100" placeholder="Chattogram" class="w-full text-xs rounded-lg border-slate-200">
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-500">Area / Thana</label>
                                    <input type="text" :name="`addresses[${index}][area]`" x-model="a.area" maxlength="100" placeholder="Pahartali" class="w-full text-xs rounded-lg border-slate-200">
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-semibold text-slate-500">Full address</label>
                                <textarea :name="`addresses[${index}][address_line]`" x-model="a.address_line" rows="2" placeholder="House, road, landmark" class="w-full text-xs rounded-lg border-slate-200"></textarea>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="space-y-4 lg:sticky lg:top-6">

            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Account type</h3>

                <div>
                    <label class="{{ $label }}">Type *</label>
                    <select name="user_type" x-model="userType" @if ($isSelf) @change="userType = 'admin'" @endif class="{{ $input }}">
                        <option value="admin">Admin (can sign in to this panel)</option>
                        <option value="customer">Customer (storefront only)</option>
                    </select>
                    @if ($isSelf)
                        <p class="text-[10px] text-slate-400 mt-1">You cannot change your own account type.</p>
                    @endif
                    @error('user_type') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                <label class="flex items-center justify-between {{ $isSelf ? 'opacity-60' : 'cursor-pointer' }}">
                    <span>
                        <span class="block text-xs font-medium text-slate-700">Active</span>
                        <span class="block text-[11px] text-slate-500">Disabled users are signed out and cannot log in.</span>
                    </span>
                    <span class="relative inline-flex items-center shrink-0">
                        <input type="checkbox" name="status" value="1" @checked($isActive) @disabled($isSelf) class="sr-only peer">
                        <span class="{{ $toggle }}"></span>
                    </span>
                </label>
                {{-- a disabled checkbox is not submitted, so keep the account enabled --}}
                @if ($isSelf) <input type="hidden" name="status" value="1"> @endif
                @error('status') <span class="{{ $err }}">{{ $message }}</span> @enderror

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.users.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Create user' }}
                    </button>
                </div>
            </section>

            @if ($isEdit)
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">History</h3>
                    <dl class="text-xs space-y-2">
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Joined</dt><dd class="font-semibold text-slate-800 text-right">{{ $user->created_at?->format('d M Y') }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Last updated</dt><dd class="font-semibold text-slate-800 text-right">{{ $user->updated_at?->diffForHumans() }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-400">Email verified</dt><dd class="font-semibold text-slate-800 text-right">{{ $user->email_verified_at ? $user->email_verified_at->format('d M Y') : 'Not yet' }}</dd></div>
                    </dl>
                </section>
            @endif
        </aside>
    </div>
</form>

<script>
    function userForm(cfg) {
        let uid = 0;
        const withKey = a => ({ ...a, _key: ++uid });

        return {
            userType: cfg.userType,
            name: cfg.name || '',
            phone: cfg.phone || '',

            password: '',
            showPassword: false,
            generated: false,

            addresses: cfg.addresses.map(withKey),

            /* ---------- password ---------- */
            generatePassword() {
                const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%&*?'];
                const all = sets.join('');
                const pick = chars => chars[crypto.getRandomValues(new Uint32Array(1))[0] % chars.length];

                // at least one character from every set, then fill up to 14
                const out = sets.map(pick);
                while (out.length < 14) out.push(pick(all));

                // shuffle
                for (let i = out.length - 1; i > 0; i--) {
                    const j = crypto.getRandomValues(new Uint32Array(1))[0] % (i + 1);
                    [out[i], out[j]] = [out[j], out[i]];
                }

                this.password = out.join('');
                this.showPassword = true;
                this.generated = true;
            },

            /* ---------- addresses ---------- */
            addAddress() {
                if (this.addresses.length >= 10) return;
                this.addresses.push(withKey({
                    id: null, address_type: 'shipping', full_name: this.name, phone: this.phone,
                    city: '', area: '', address_line: '', is_default: false,
                }));
                this.normalizeDefaults();
            },
            removeAddress(index) {
                this.addresses.splice(index, 1);
                this.normalizeDefaults();
            },
            makeDefault(index) {
                const type = this.addresses[index].address_type;
                this.addresses.forEach((a, i) => { if (a.address_type === type) a.is_default = (i === index); });
            },
            fillFromAccount(a) {
                a.full_name = this.name;
                a.phone = this.phone;
            },
            // exactly one default per type (the server enforces this too)
            normalizeDefaults() {
                ['shipping', 'billing'].forEach(type => {
                    const same = this.addresses.filter(a => a.address_type === type);
                    if (same.length === 0) return;
                    const flagged = same.filter(a => a.is_default);
                    if (flagged.length === 0) same[0].is_default = true;
                    if (flagged.length > 1) flagged.slice(1).forEach(a => a.is_default = false);
                });
            },
        };
    }
</script>
