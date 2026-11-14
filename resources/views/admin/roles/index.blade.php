@extends('layouts.admin')

@section('title', 'Role Management')

@section('content')
    <div class="space-y-5" x-data="roleIndex()" @keydown.escape.window="close()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Roles and permissions</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $roles->count() }} {{ Str::plural('role', $roles->count()) }} · {{ $totalPermissions }} {{ Str::plural('permission', $totalPermissions) }} available</p>
            </div>
            <a href="{{ route('admin.roles.create') }}"
               class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create role
            </a>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.roles.index') }}" x-ref="filter"
              class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs flex gap-2">
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Search roles"
                       @input.debounce.500ms="$refs.filter.submit()"
                       @if ($q !== '') x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)" @endif
                       class="w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            @if ($q !== '')
                <a href="{{ route('admin.roles.index') }}" class="px-3 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 rounded-xl border border-slate-200 text-center">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Role</th>
                        <th class="py-3.5 px-6">Access</th>
                        <th class="py-3.5 px-6">Permissions</th>
                        <th class="py-3.5 px-6">Users</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($roles as $role)
                        @php
                            $count   = $role->permissions->count();
                            $percent = $totalPermissions > 0 ? round($count / $totalPermissions * 100) : 0;
                            $preview = $role->permissions->sortBy('name')->take(4);
                            $more    = $count - $preview->count();
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-6">
                                <button type="button" @click="open({{ $role->id }})" class="flex items-center gap-3 text-left group">
                                <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100 grid place-items-center shrink-0">
                                    <svg class="w-4.5 h-4.5" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </span>
                                    <span>
                                    <span class="block font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ $role->name }}</span>
                                    @if ($role->is_protected)
                                            <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 uppercase tracking-wide">Protected</span>
                                        @endif
                                </span>
                                </button>
                            </td>

                            <td class="py-3.5 px-6 w-44">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full {{ $percent === 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-[11px] text-slate-500 tabular-nums w-9 text-right">{{ $percent }}%</span>
                                </div>
                            </td>

                            <td class="py-3.5 px-6">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($preview as $permission)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">{{ $permission->name }}</span>
                                    @empty
                                        <span class="text-[11px] text-slate-400">No permissions</span>
                                    @endforelse
                                    @if ($more > 0)
                                        <button type="button" @click="open({{ $role->id }})" class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200">+{{ $more }} more</button>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-6">
                                @if ($role->users_count > 0)
                                    <a href="{{ route('admin.users.index', ['role' => $role->name]) }}" class="text-slate-700 font-semibold hover:text-indigo-600">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}</a>
                                @else
                                    <span class="text-slate-400">None</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="open({{ $role->id }})" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">View</button>
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</a>

                                    @unless ($role->is_protected)
                                        <form id="delete-role-{{ $role->id }}" action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    onclick="confirmDelete('delete-role-{{ $role->id }}', 'This role will be removed. It can only be deleted when no user has it.')"
                                                    class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-12 text-slate-400">
                                @if ($q !== '')
                                    No roles match "{{ $q }}". <a href="{{ route('admin.roles.index') }}" class="text-indigo-600 font-semibold">Clear search</a>
                                @else
                                    No roles yet. <a href="{{ route('admin.roles.create') }}" class="text-indigo-600 font-semibold">Create the first one</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===================== DETAILS MODAL ===================== --}}
        <div x-show="r" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="r ? r.name : ''">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" x-show="r" x-transition.opacity @click="close()"></div>

            <div class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl"
                 x-show="r" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                <template x-if="r">
                    <div>
                        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-slate-800 truncate" x-text="r.name"></h3>
                                    <span x-show="r.is_protected" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 text-slate-500 uppercase tracking-wide">Protected</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5" x-text="`Created ${r.created_at || '-'}`"></p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="r.edit_url" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700">Edit role</a>
                                <button type="button" @click="close()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 space-y-6 text-xs">
                            <div>
                                <h4 class="font-bold text-slate-800 mb-2" x-text="`Permissions (${r.permissions.length})`"></h4>
                                <p x-show="r.permissions.length === 0" class="text-slate-400">This role has no permissions, so its users cannot open any section.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <template x-for="p in r.permissions" :key="p">
                                        <div class="flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-100 bg-indigo-50/40">
                                            <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <span class="font-semibold text-slate-800" x-text="p"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-bold text-slate-800" x-text="`Users (${r.users_count})`"></h4>
                                    <a x-show="r.users_count > 0" :href="r.users_url" class="font-semibold text-indigo-600 hover:text-indigo-800">View all &rarr;</a>
                                </div>
                                <p x-show="r.users_count === 0" class="text-slate-400">Nobody has this role yet.</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="n in r.users" :key="n">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700" x-text="n"></span>
                                    </template>
                                    <span x-show="r.users_count > r.users.length" class="px-2.5 py-1 text-[11px] text-slate-400" x-text="`and ${r.users_count - r.users.length} more`"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        const ROLE_DETAILS = @json($details);

        function roleIndex() {
            return {
                r: null,

                open(id) {
                    this.r = ROLE_DETAILS.find(x => x.id === id) || null;
                    if (this.r) document.body.classList.add('overflow-hidden');
                },
                close() {
                    this.r = null;
                    document.body.classList.remove('overflow-hidden');
                },
            };
        }
    </script>
@endsection
