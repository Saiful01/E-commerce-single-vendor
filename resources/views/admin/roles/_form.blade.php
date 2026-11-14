{{--
    Shared form for create + edit.
    Expects: $action, $method ('POST' | 'PUT'), $role (null on create), $permissions, $assigned (names)
--}}
@php
    $isEdit      = ! is_null($role);
    $isProtected = $isEdit && $role->is_protected;

    $cfg = [
        'names'    => $permissions->pluck('name')->values()->all(),
        'labels'   => $permissions->mapWithKeys(fn ($p) => [$p->name => \Illuminate\Support\Str::headline($p->name)])->all(),
        'selected' => array_values((array) old('permissions', $assigned)),
        'name'     => old('name', $role->name ?? ''),
    ];

    $input = 'w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500';
    $label = 'block text-xs font-semibold text-slate-700 mb-1';
    $card  = 'bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4';
    $err   = 'text-rose-500 text-[11px] mt-1 block';
@endphp

<form action="{{ $action }}" method="POST" x-data="roleForm({{ \Illuminate\Support\Js::from($cfg) }})" class="space-y-4">
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

        {{-- ======================= PERMISSIONS ======================= --}}
        <section class="lg:col-span-2 {{ $card }}">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Permissions</h3>
                    <p class="text-[11px] text-slate-500">Choose what people with this role can open in the admin panel.</p>
                </div>

                <button type="button" @click="toggleVisible()" x-show="visibleNames.length > 0"
                        class="px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 shrink-0"
                        x-text="allVisibleSelected ? 'Clear shown' : 'Select all shown'"></button>
            </div>

            @if ($permissions->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-200 py-10 px-4 text-center">
                    <p class="text-sm font-semibold text-slate-700">No permissions in the database yet</p>
                    <p class="text-xs text-slate-500 mt-1">Create them by running:</p>
                    <code class="inline-block mt-2 px-3 py-1.5 rounded-lg bg-slate-900 text-slate-100 text-[11px]">php artisan db:seed --class=PermissionSeeder</code>
                </div>
            @else
                <div class="relative">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" x-model="search" placeholder="Search permissions" class="{{ $input }} pl-9">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @foreach ($permissions as $permission)
                        <label x-show="matches({{ \Illuminate\Support\Js::from($permission->name) }})"
                               class="flex items-start gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer transition-colors hover:border-indigo-200 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50/50">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" x-model="selected"
                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="min-w-0">
                                <span class="block text-xs font-semibold text-slate-800">{{ \Illuminate\Support\Str::headline($permission->name) }}</span>
                                <span class="block text-[10px] font-mono text-slate-400 truncate">{{ $permission->name }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>

                <p x-show="visibleNames.length === 0" x-cloak class="text-xs text-slate-500 text-center py-6">
                    No permission matches "<span x-text="search"></span>".
                </p>
            @endif
            @error('permissions') <span class="{{ $err }}">{{ $message }}</span> @enderror
            @error('permissions.*') <span class="{{ $err }}">{{ $message }}</span> @enderror
        </section>

        {{-- ======================= SIDEBAR ======================= --}}
        <aside class="space-y-4 lg:sticky lg:top-6">

            <section class="{{ $card }}">
                <h3 class="text-sm font-bold text-slate-800">Role</h3>

                <div>
                    <label class="{{ $label }}">Role name *</label>
                    <input type="text" name="name" x-model="name" maxlength="50" placeholder="e.g. Content Manager"
                           @if ($isProtected) readonly @endif
                           class="{{ $input }} {{ $isProtected ? 'bg-slate-50 text-slate-500' : '' }}">
                    @if ($isProtected)
                        <p class="text-[10px] text-slate-400 mt-1">This is a protected role, so its name cannot be changed.</p>
                    @endif
                    @error('name') <span class="{{ $err }}">{{ $message }}</span> @enderror
                </div>

                {{-- Selection summary --}}
                <div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Selected</span>
                        <span class="text-slate-500"><span class="font-bold text-slate-800" x-text="selected.length"></span> of <span x-text="names.length"></span></span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden" role="progressbar" :aria-valuenow="percent" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-indigo-600 transition-all duration-300" :style="{ width: percent + '%' }"></div>
                    </div>
                </div>

                <div class="pt-2 flex gap-2">
                    <a href="{{ route('admin.roles.index') }}" class="flex-1 text-center px-4 py-2.5 border border-slate-200 text-slate-600 font-semibold text-xs rounded-xl hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all">
                        {{ $isEdit ? 'Save changes' : 'Create role' }}
                    </button>
                </div>
            </section>

            @if ($isEdit)
                <section class="{{ $card }}">
                    <h3 class="text-sm font-bold text-slate-800">Used by</h3>
                    <p class="text-xs text-slate-600">
                        <span class="font-bold text-slate-800">{{ $role->users_count }}</span>
                        {{ \Illuminate\Support\Str::plural('user', $role->users_count) }} have this role.
                    </p>
                    @if ($role->users_count > 0)
                        <a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">See who &rarr;</a>
                    @endif
                </section>
            @endif
        </aside>
    </div>
</form>

<script>
    function roleForm(cfg) {
        return {
            names: cfg.names,
            labels: cfg.labels,
            selected: cfg.selected,
            name: cfg.name || '',
            search: '',

            matches(name) {
                const q = this.search.trim().toLowerCase();
                if (!q) return true;
                return name.toLowerCase().includes(q) || (this.labels[name] || '').toLowerCase().includes(q);
            },

            get visibleNames() {
                return this.names.filter(n => this.matches(n));
            },
            get allVisibleSelected() {
                return this.visibleNames.length > 0 && this.visibleNames.every(n => this.selected.includes(n));
            },
            get percent() {
                return this.names.length ? Math.round((this.selected.length / this.names.length) * 100) : 0;
            },

            // Select (or clear) only what the current search is showing
            toggleVisible() {
                if (this.allVisibleSelected) {
                    this.selected = this.selected.filter(n => !this.visibleNames.includes(n));
                } else {
                    this.selected = Array.from(new Set([...this.selected, ...this.visibleNames]));
                }
            },
        };
    }
</script>
