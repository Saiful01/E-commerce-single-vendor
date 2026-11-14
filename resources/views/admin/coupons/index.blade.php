@extends('layouts.admin')

@section('title', 'Coupon Management')

@section('content')
    @php
        $stateStyles = [
            'live'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'scheduled' => 'bg-sky-50 text-sky-700 border-sky-200',
            'expired'   => 'bg-amber-50 text-amber-700 border-amber-200',
            'exhausted' => 'bg-violet-50 text-violet-700 border-violet-200',
            'inactive'  => 'bg-slate-100 text-slate-600 border-slate-200',
        ];

        $tabBase = 'px-3.5 py-2 text-xs font-semibold rounded-xl border whitespace-nowrap transition-colors';
        $tabOn   = 'bg-indigo-600 text-white border-indigo-600 shadow-sm';
        $tabOff  = 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600';
    @endphp

    <div class="space-y-5" x-data="couponIndex()" @keydown.escape.window="close()">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Coupons</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $total }} {{ Str::plural('coupon', $total) }} · {{ $counts['live'] }} live right now</p>
            </div>
            <a href="{{ route('admin.coupons.create') }}"
               class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add coupon
            </a>
        </div>

        {{-- State tabs --}}
        <nav class="flex gap-2 overflow-x-auto pb-1" aria-label="Coupon status">
            <a href="{{ route('admin.coupons.index', array_filter(['q' => $q])) }}" class="{{ $tabBase }} {{ $state ? $tabOff : $tabOn }}">
                All <span class="opacity-70 ml-1">{{ $total }}</span>
            </a>
            @foreach ($stateLabels as $key => $name)
                <a href="{{ route('admin.coupons.index', array_filter(['state' => $key, 'q' => $q])) }}" class="{{ $tabBase }} {{ $state === $key ? $tabOn : $tabOff }}">
                    {{ $name }} <span class="opacity-70 ml-1">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.coupons.index') }}" x-ref="filter"
              class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs flex gap-2">
            @if ($state) <input type="hidden" name="state" value="{{ $state }}"> @endif

            <div class="relative flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" name="q" value="{{ $q }}" placeholder="Search by code, name or description"
                       @input.debounce.500ms="$refs.filter.submit()"
                       @if ($q !== '') x-init="$el.focus(); $el.setSelectionRange($el.value.length, $el.value.length)" @endif
                       class="w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            @if ($q !== '')
                <a href="{{ route('admin.coupons.index', array_filter(['state' => $state])) }}" class="px-3 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 rounded-xl border border-slate-200 text-center">Clear</a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Coupon</th>
                        <th class="py-3.5 px-6">Discount</th>
                        <th class="py-3.5 px-6">Usage</th>
                        <th class="py-3.5 px-6">Validity</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($coupons as $coupon)
                        @php
                            $percentUsed = $coupon->usage_limit ? min(100, round($coupon->used_count / $coupon->usage_limit * 100)) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="open({{ $coupon->id }})" class="font-mono text-[13px] font-bold tracking-wide text-slate-800 hover:text-indigo-600 transition-colors">{{ $coupon->code }}</button>
                                    <button type="button" @click="copy('{{ $coupon->code }}')" class="p-1 text-slate-400 hover:text-indigo-600 rounded" title="Copy code" aria-label="Copy code {{ $coupon->code }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                                @if ($coupon->name)
                                    <p class="text-[11px] text-slate-500 mt-0.5 truncate max-w-[14rem]">{{ $coupon->name }}</p>
                                @endif
                            </td>

                            <td class="py-3.5 px-6">
                                <p class="font-bold text-slate-800">{{ $coupon->discount_label }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    {{ \App\Models\Coupon::APPLIES_TO[$coupon->applies_to] ?? $coupon->applies_to }}
                                    @if ($coupon->min_order_label) · {{ $coupon->min_order_label }} @endif
                                </p>
                            </td>

                            <td class="py-3.5 px-6 w-40">
                                <p class="text-slate-700 font-semibold">{{ $coupon->usage_label }}</p>
                                @if (! is_null($percentUsed))
                                    <div class="mt-1.5 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full {{ $percentUsed >= 100 ? 'bg-violet-500' : 'bg-indigo-500' }}" style="width: {{ $percentUsed }}%"></div>
                                    </div>
                                @endif
                            </td>

                            <td class="py-3.5 px-6 text-slate-500">{{ $coupon->validity_label }}</td>

                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-3">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border {{ $stateStyles[$coupon->state] }}" data-state-badge>{{ $stateLabels[$coupon->state] }}</span>

                                    <div x-data="{ on: {{ $coupon->is_active ? 'true' : 'false' }}, busy: false }">
                                        <label class="relative inline-flex items-center cursor-pointer" title="Switch this coupon on or off">
                                            <input type="checkbox" class="sr-only peer" :checked="on" :disabled="busy" aria-label="Active: {{ $coupon->code }}"
                                                   @change="busy = true; toggle({{ $coupon->id }}, $event.target).then(v => { on = v; busy = false })">
                                            <span class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400 peer-disabled:opacity-60 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></span>
                                        </label>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button type="button" @click="open({{ $coupon->id }})" class="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors">View</button>
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors">Edit</a>

                                    <form action="{{ route('admin.coupons.duplicate', $coupon) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors" title="Make a copy with a new code">Copy</button>
                                    </form>

                                    <form id="delete-coupon-{{ $coupon->id }}" action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                onclick="confirmDelete('delete-coupon-{{ $coupon->id }}', 'A coupon that has been used cannot be deleted. Switch it off instead.')"
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition-colors">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                @if ($q !== '' || $state)
                                    No coupons match your filters. <a href="{{ route('admin.coupons.index') }}" class="text-indigo-600 font-semibold">Clear filters</a>
                                @else
                                    No coupons yet. <a href="{{ route('admin.coupons.create') }}" class="text-indigo-600 font-semibold">Create your first coupon</a>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $coupons->links() }}
            </div>
        </div>

        {{-- ===================== DETAILS MODAL ===================== --}}
        <div x-show="c" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" :aria-label="c ? c.code : ''">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" x-show="c" x-transition.opacity @click="close()"></div>

            <div class="relative bg-white w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl"
                 x-show="c" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]" x-transition:enter-end="opacity-100 translate-y-0 scale-100">

                <template x-if="c">
                    <div>
                        <div class="sticky top-0 z-10 bg-white/95 backdrop-blur border-b border-slate-100 px-6 py-4 flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-slate-800 font-mono tracking-wide truncate" x-text="c.code"></h3>
                                    <button type="button" @click="copy(c.code)" class="p-1 text-slate-400 hover:text-indigo-600 rounded" title="Copy code" aria-label="Copy code">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-0.5 truncate" x-text="c.name || 'No internal name'"></p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a :href="c.edit_url" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700">Edit coupon</a>
                                <button type="button" @click="close()" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100" aria-label="Close">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 space-y-6 text-xs">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-1 rounded-md text-[10px] font-bold border" :class="badge(c.state)" x-text="c.state_label"></span>
                                <span x-show="c.first_order_only" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">First order only</span>
                                <span x-show="c.exclude_discounted" class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">Skips sale items</span>
                            </div>

                            <div class="rounded-xl border-2 border-dashed border-indigo-300 bg-indigo-50/40 p-4 text-center">
                                <p class="text-lg font-extrabold text-indigo-700" x-text="c.discount_label"></p>
                                <p class="text-slate-600 mt-1" x-show="c.description" x-text="c.description"></p>
                            </div>

                            <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-3 bg-slate-50 rounded-xl p-4">
                                <div><dt class="text-slate-400">Type</dt><dd class="font-semibold text-slate-800" x-text="c.type"></dd></div>
                                <div><dt class="text-slate-400">Minimum order</dt><dd class="font-semibold text-slate-800" x-text="c.min_order || 'None'"></dd></div>
                                <div><dt class="text-slate-400">Validity</dt><dd class="font-semibold text-slate-800" x-text="c.validity"></dd></div>
                                <div><dt class="text-slate-400">Times used</dt><dd class="font-semibold text-slate-800" x-text="c.usage_label"></dd></div>
                                <div><dt class="text-slate-400">Per customer</dt><dd class="font-semibold text-slate-800" x-text="c.per_user_limit ? `${c.per_user_limit} time(s)` : 'Unlimited'"></dd></div>
                                <div><dt class="text-slate-400">Discount given</dt><dd class="font-semibold text-slate-800" x-text="c.total_discount"></dd></div>
                                <div><dt class="text-slate-400">Created</dt><dd class="font-semibold text-slate-800" x-text="c.created_at || '-'"></dd></div>
                                <div><dt class="text-slate-400">Last updated</dt><dd class="font-semibold text-slate-800" x-text="c.updated_at || '-'"></dd></div>
                            </dl>

                            <div>
                                <h4 class="font-bold text-slate-800 mb-2" x-text="`Applies to: ${c.applies_label}`"></h4>
                                <div class="flex flex-wrap gap-1.5" x-show="c.targets.length > 0">
                                    <template x-for="t in c.targets" :key="t">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-100 text-slate-700" x-text="t"></span>
                                    </template>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-bold text-slate-800 mb-2">Recent uses</h4>
                                <p x-show="c.recent_uses.length === 0" class="text-slate-400">Nobody has used this coupon yet.</p>
                                <ul class="divide-y divide-slate-100 rounded-xl border border-slate-200" x-show="c.recent_uses.length > 0">
                                    <template x-for="(u, i) in c.recent_uses" :key="i">
                                        <li class="flex items-center justify-between gap-3 px-3 py-2">
                                            <span class="font-semibold text-slate-700" x-text="u.user"></span>
                                            <span class="text-slate-400" x-text="u.date"></span>
                                            <span class="font-semibold text-slate-800" x-text="u.amount"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        const COUPON_DETAILS = @json($details);

        function couponIndex() {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            const toast = (icon, text) => {
                if (typeof Swal === 'undefined') return;
                Swal.fire({ icon, text, toast: true, position: 'top-end', showConfirmButton: false, timer: 2200 });
            };

            const STATE_CLASSES = {
                live: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                scheduled: 'bg-sky-50 text-sky-700 border-sky-200',
                expired: 'bg-amber-50 text-amber-700 border-amber-200',
                exhausted: 'bg-violet-50 text-violet-700 border-violet-200',
                inactive: 'bg-slate-100 text-slate-600 border-slate-200',
            };

            return {
                c: null,

                badge(state) { return STATE_CLASSES[state] || STATE_CLASSES.inactive; },

                open(id) {
                    this.c = COUPON_DETAILS.find(x => x.id === id) || null;
                    if (this.c) document.body.classList.add('overflow-hidden');
                },
                close() {
                    this.c = null;
                    document.body.classList.remove('overflow-hidden');
                },

                copy(code) {
                    const done = () => toast('success', `Copied ${code}`);
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(code).then(done, () => toast('error', 'Could not copy'));
                    } else {
                        const el = document.createElement('textarea');
                        el.value = code;
                        document.body.appendChild(el);
                        el.select();
                        try { document.execCommand('copy'); done(); } catch (e) { toast('error', 'Could not copy'); }
                        el.remove();
                    }
                },

                toggle(id, checkbox) {
                    const wanted = checkbox.checked;

                    return fetch(`{{ url('admin/coupons') }}/${id}/toggle`, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    })
                        .then(async res => {
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok || !data.success) throw new Error(data.message || `Request failed (HTTP ${res.status})`);

                            const badge = checkbox.closest('tr').querySelector('[data-state-badge]');
                            if (badge) {
                                badge.textContent = data.state_label;
                                badge.className = `px-2.5 py-1 rounded-md text-[10px] font-bold border ${this.badge(data.state)}`;
                            }

                            const detail = COUPON_DETAILS.find(x => x.id === id);
                            if (detail) { detail.state = data.state; detail.state_label = data.state_label; }

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
