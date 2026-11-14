@extends('layouts.admin')

@section('title', 'User Management')

@section('content')
    @php
        $tabBase = 'px-3.5 py-2 text-xs font-semibold rounded-xl border whitespace-nowrap transition-colors';
        $tabOn   = 'bg-indigo-600 text-white border-indigo-600 shadow-sm';
        $tabOff  = 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600';
        $filters = array_filter(['q' => $q, 'status' => $status, 'role' => $role]);
    @endphp

    <div class="space-y-5" x-data="userIndex()" @keydown.escape.window="close()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Users</h2>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $total }} {{ Str::plural('account', $total) }}
                    @if ($inactive > 0) <span class="text-slate-400">·</span> {{ $inactive }} disabled @endif
                </p>
            </div>
            <a href="{{ route('admin.users.create', array_filter(['type' => $type])) }}"
               class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add user
            </a>
        </div>

        {{-- Type tabs --}}
        <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="Account types">
            <a href="{{ route('admin.users.index', $filters) }}" class="{{ $tabBase }} {{ $type ? $tabOff : $tabOn }}">
                All <span class="opacity-70 ml-1">{{ $total }}</span>
            </a>
            <a href="{{ route('admin.users.index', $filters + ['type' => 'admin']) }}" class="{{ $tabBase }} {{ $type === 'admin' ? $tabOn : $tabOff }}">
                Admins <span class="opacity-70 ml-1">{{ $counts['admin'] ?? 0 }}</span>
            </a>
            <a href="{{ route('admin.users.index', $filters + ['type' => 'customer']) }}" class="{{ $tabBase }} {{ $type === 'customer' ? $tabOn : $tabOff }}">
                Customers <span class="opacity-70 ml-1">{{ $counts['customer'] ?? 0 }}</span>
            </a>
        </nav>

        {{-- Search & filters --}}
        <form method="GET" action="{{ route('admin.users.index') }}" x-ref="filter"
              class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-2">
            @if ($type) <input type="hidden" name="type" value="{{ $type }}"> @endif

            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Search by name, email or phone"
                       @input.debounce.500ms="$refs.filter.submit()"
                       @if ($q !== '') x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)" @endif
                       class="w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            @if ($type !== 'customer' && $roles->isNotEmpty())
                <select name="role" @change="$refs.filter.submit()" class="text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All roles</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                    @endforeach
                </select>
            @endif

            <select name="status" @change="$refs.filter.submit()" class="text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Any status</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Disabled</option>
            </select>

            @if ($q !== '' || $status || $role)
                <a href="{{ route('admin.users.index', array_filter(['type' => $type])) }}" class="px-3 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 rounded-xl border border-slate-200 text-center">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">User</th>
                        <th class="py-3.5 px-6">Type & roles</th>
                        <th class="py-3.5 px-6">Addresses</th>
                        <th class="py-3.5 px-6">Active</th>
                        <th class="py-3.5 px-6">Joined</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($users as $user)
                        @php
                            $isSelf     = $user->id === auth()->id();
                            $isAdminRow = $user->user_type === 'admin';
                            $default    = $user->addresses->first(fn ($a) => $a->address_type === 'shipping' && $a->is_default)
                                          ?? $user->addresses->first();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-6">
                                <button type="button" @click="open({{ $user->id }})" class="flex items-center gap-3 text-left group">
                                <span class="w-9 h-9 rounded-xl border flex items-center justify-center font-bold uppercase shrink-0 {{ $isAdminRow ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100' }}">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </span>
                                    <span class="min-w-0">
                                    <span class="block font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors truncate">
                                        {{ $user->name }} @if ($isSelf) <span class="text-[10px] font-bold text-slate-400">(you)</span> @endif
                                    </span>
                                    <span class="block text-[11px] text-slate-500 truncate">{{ $user->email }}</span>
                                    @if ($user->phone) <span class="block text-[11px] text-slate-400">{{ $user->phone }}</span> @endif
                                </span>
                                </button>
                            </td>

                            <td class="py-4 px-6">
                                <div class="flex flex-wrap gap-1">
                                    @if ($isAdminRow)
                                        @forelse ($user->roles as $r)
                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">{{ $r->name }}</span>
                                        @empty
                                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Admin, no role</span>
                                        @endforelse
                                    @else
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Customer</span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-4 px-6">
                                @if ($user->addresses->isNotEmpty())
                                    <p class="text-slate-700 font-semibold">{{ $user->addresses->count() }} saved</p>
                                    <p class="text-[11px] text-slate-400 truncate max-w-[10rem]">{{ $default->area }}, {{ $default->city }}</p>
                                @else
                                    <span class="text-[11px] text-slate-400">None</span>
                                @endif
                            </td>

                            <td class="py-4 px-6">
                                <div x-data="{ on: {{ $user->status ? 'true' : 'false' }}, busy: false }">
                                    <label class="relative inline-flex items-center {{ $isSelf ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}"
                                           title="{{ $isSelf ? 'You cannot disable your own account' : 'Enable or disable this account' }}">
                                        <input type="checkbox" class="sr-only peer" :checked="on" :disabled="busy || {{ $isSelf ? 'true' : 'false' }}"
                                               @change="busy = true; toggle({{ $user->id }}, $event.target).then(v => { on = v; busy = false })">
                                        <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                                    </label>
                                </div>
                            </td>

                            <td class="py-4 px-6 text-slate-500">{{ $user->created_at?->format('d M Y') }}</td>

                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="open({{ $user->id }})" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">View</button>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</a>

                                    @unless ($isSelf)
                                        <form id="delete-user-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="confirmDelete('delete-user-{{ $user->id }}', 'This account and all of its saved addresses will be permanently removed.')"
                                                    class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                @if ($q !== '' || $status || $role || $type)
                                    No users match your filters. <a href="{{ route('admin.users.index') }}" class="text-indigo-600 font-semibold">Clear filters</a>
                                @else
                                    No users yet. <a href="{{ route('admin.users.create') }}" class="text-indigo-600 font-semibold">Add the first one</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        </div>

        {{-- ===================== DETAILS MODAL ===================== --}}
        <div x-show="u" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="u ? u.name : ''">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" x-show="u" x-transition.opacity @click="close()"></div>

            <div class="relative bg-white w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl"
                 x-show="u" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                <template x-if="u">
                    <div>
                        {{-- header --}}
                        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                            <span class="w-11 h-11 rounded-xl border flex items-center justify-center font-bold uppercase text-lg shrink-0"
                                  :class="u.user_type === 'admin' ? 'bg-indigo-50 text-indigo-600 border-indigo-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'"
                                  x-text="u.name.charAt(0)"></span>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-slate-800 truncate" x-text="u.name"></h3>
                                    <p class="text-[11px] text-slate-500 truncate" x-text="u.email"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="u.edit_url" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700">Edit user</a>
                                <button type="button" @click="close()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 space-y-6 text-xs">

                            <div class="flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                                  :class="u.user_type === 'admin' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                                  x-text="u.user_type === 'admin' ? 'Admin' : 'Customer'"></span>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                                      :class="u.status ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                      x-text="u.status ? 'Active' : 'Disabled'"></span>
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                                      :class="u.verified_at ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-slate-100 text-slate-600 border-slate-200'"
                                      x-text="u.verified_at ? 'Email verified' : 'Email not verified'"></span>
                                <span x-show="u.is_self" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">This is you</span>
                            </div>

                            <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3 bg-slate-50 rounded-xl p-4">
                                <div><dt class="text-slate-400">Email</dt><dd class="font-semibold text-slate-800 break-all" x-text="u.email"></dd></div>
                                <div><dt class="text-slate-400">Phone</dt><dd class="font-semibold text-slate-800" x-text="u.phone || 'Not set'"></dd></div>
                                <div><dt class="text-slate-400">Saved addresses</dt><dd class="font-semibold text-slate-800" x-text="u.addresses.length"></dd></div>
                                <div><dt class="text-slate-400">Joined</dt><dd class="font-semibold text-slate-800" x-text="u.created_at || '-'"></dd></div>
                                <div><dt class="text-slate-400">Last updated</dt><dd class="font-semibold text-slate-800" x-text="u.updated_at || '-'"></dd></div>
                                <div><dt class="text-slate-400">Email verified</dt><dd class="font-semibold text-slate-800" x-text="u.verified_at || 'Not yet'"></dd></div>
                            </dl>

                            <div x-show="u.user_type === 'admin'">
                                <h4 class="font-bold text-slate-800 mb-2">Roles</h4>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="r in u.roles" :key="r">
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200" x-text="r"></span>
                                    </template>
                                    <span x-show="u.roles.length === 0" class="text-slate-400">No role assigned. This admin cannot open any section.</span>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-bold text-slate-800 mb-2" x-text="`Addresses (${u.addresses.length})`"></h4>

                                <p x-show="u.addresses.length === 0" class="text-slate-400">No saved addresses.</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <template x-for="(a, i) in u.addresses" :key="i">
                                        <div class="rounded-xl border p-3.5 space-y-1.5" :class="a.is_default ? 'border-indigo-300 bg-indigo-50/30' : 'border-slate-200'">
                                            <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                                  :class="a.type === 'shipping' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-sky-50 text-sky-700 border-sky-200'"
                                                  x-text="a.type === 'shipping' ? 'Shipping' : 'Billing'"></span>
                                                <span x-show="a.is_default" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-600 text-white">Default</span>
                                            </div>
                                            <p class="font-semibold text-slate-800" x-text="a.full_name"></p>
                                            <p class="text-slate-500" x-text="a.phone"></p>
                                            <p class="text-slate-600 leading-relaxed" x-text="`${a.address_line}, ${a.area}, ${a.city}`"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        const USER_DETAILS = @json($details);

        function userIndex() {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const toast = (icon, text) => {
                if (typeof Swal === 'undefined') return;
                Swal.fire({ icon, text, toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
            };

            return {
                u: null,

                open(id) {
                    this.u = USER_DETAILS.find(x => x.id === id) || null;
                    if (this.u) document.body.classList.add('overflow-hidden');
                },
                close() {
                    this.u = null;
                    document.body.classList.remove('overflow-hidden');
                },

                toggle(id, checkbox) {
                    const wanted = checkbox.checked;

                    return fetch(`{{ url('admin/users') }}/${id}/toggle`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || !data.success) throw new Error(data.message || `Request failed (HTTP ${res.status})`);

                            const detail = USER_DETAILS.find(x => x.id === id);
                            if (detail) detail.status = data.status;

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
