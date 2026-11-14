    @php
        // Returns link: shown right after "Orders" (single link or inside the Orders group).
        // If no Orders item is found in $menu, it is added at the end of the list instead.
        $returnsShown = false;
        $returnsIcon  = 'M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3';
        $returnsOn    = request()->routeIs('admin.order-returns.*');
    @endphp

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'w-64' : 'w-20'" class="h-screen sticky top-0 shrink-0 bg-slate-900 text-white transition-all duration-300 ease-in-out flex flex-col">

        <!-- Brand Logo / Name -->
        <div class="h-16 shrink-0 flex items-center justify-between px-4 bg-slate-950">
            <div x-show="sidebarOpen" class="flex items-center gap-2 min-w-0">
                @if ($settings->logo_url)
                    <img src="{{ $settings->logo_url }}" alt="{{ $settings->site_name }}" class="h-8 max-w-[9rem] object-contain">
                @else
                    <span class="font-bold text-lg tracking-wider text-indigo-400 truncate">{{ $settings->site_name }}</span>
                @endif
            </div>
            <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white focus:outline-none" aria-label="Toggle sidebar">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 min-h-0 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
            @foreach ($menu as $item)

                {{-- ============ GROUP (dropdown) ============ --}}
                @if (isset($item['children']))
                    @php
                        $children = collect($item['children'])->filter(fn ($c) => $userCan($c['can']))->values();
                        $hasOrders = $children->contains(fn ($c) => ($c['route'] ?? '') === 'admin.orders.index');
                        $groupActive = $children->contains(fn ($c) => request()->routeIs($c['match'])) || ($hasOrders && $returnsOn);
                    @endphp

                    @if ($children->isNotEmpty())
                        <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                            <button type="button"
                                    @click="if (!sidebarOpen) { sidebarOpen = true; open = true } else { open = !open }"
                                    title="{{ $item['label'] }}"
                                    class="w-full flex items-center px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group focus:outline-none {{ $groupActive ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                                <svg class="w-5 h-5 flex-shrink-0 transition-transform group-hover:scale-110 {{ $groupActive ? 'text-indigo-400' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @foreach ((array) $item['icon'] as $path)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"></path>
                                    @endforeach
                                </svg>
                                <span x-show="sidebarOpen" x-transition.opacity class="ml-3 flex-1 text-left whitespace-nowrap">{{ $item['label'] }}</span>
                                <svg x-show="sidebarOpen" class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <!-- Sub menu -->
                            <div x-show="open && sidebarOpen" x-transition x-cloak class="mt-1 ml-5 pl-3 border-l border-slate-700/70 space-y-0.5">
                                @foreach ($children as $child)
                                    @php $childActive = request()->routeIs($child['match']); @endphp
                                    <a href="{{ route($child['route']) }}"
                                       class="flex items-center px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ $childActive ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-medium' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full mr-3 flex-shrink-0 {{ $childActive ? 'bg-white' : 'bg-slate-600' }}"></span>
                                        <span class="whitespace-nowrap">{{ $child['label'] }}</span>
                                    </a>

                                    {{-- Returns, right under Orders --}}
                                    @if (($child['route'] ?? '') === 'admin.orders.index')
                                        @php $returnsShown = true; @endphp
                                        <a href="{{ route('admin.order-returns.index') }}"
                                           class="flex items-center px-3 py-2 rounded-lg text-sm transition-all duration-200 {{ $returnsOn ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 font-medium' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                                            <span class="w-1.5 h-1.5 rounded-full mr-3 flex-shrink-0 {{ $returnsOn ? 'bg-white' : 'bg-slate-600' }}"></span>
                                            <span class="whitespace-nowrap">Returns</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ============ SINGLE LINK ============ --}}
                @elseif ($userCan($item['can']))
                    @php $active = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                       class="flex items-center px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ $active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                        <svg class="w-5 h-5 flex-shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @foreach ((array) $item['icon'] as $path)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $path }}"></path>
                            @endforeach
                        </svg>
                        <span x-show="sidebarOpen" x-transition.opacity class="ml-3 font-medium whitespace-nowrap">{{ $item['label'] }}</span>
                    </a>

                    {{-- Returns, right under Orders --}}
                    @if (($item['route'] ?? '') === 'admin.orders.index')
                        @php $returnsShown = true; @endphp
                        <a href="{{ route('admin.order-returns.index') }}" title="Returns"
                           class="flex items-center px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ $returnsOn ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                            <svg class="w-5 h-5 flex-shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $returnsIcon }}"></path></svg>
                            <span x-show="sidebarOpen" x-transition.opacity class="ml-3 font-medium whitespace-nowrap">Returns</span>
                        </a>
                    @endif
                @endif

            @endforeach

            {{-- Fallback: no Orders item was found in $menu, so Returns goes at the end --}}
            @unless ($returnsShown)
                <a href="{{ route('admin.order-returns.index') }}" title="Returns"
                   class="flex items-center px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ $returnsOn ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                    <svg class="w-5 h-5 flex-shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $returnsIcon }}"></path></svg>
                    <span x-show="sidebarOpen" x-transition.opacity class="ml-3 font-medium whitespace-nowrap">Returns</span>
                </a>
            @endunless

            {{-- Shop content --}}
            @php
                $pagesOn  = request()->routeIs('admin.pages.*');
                $msgOn    = request()->routeIs('admin.contact-messages.*');
                $unreadMsgs = \App\Models\ContactMessage::where('is_read', false)->count();
            @endphp
            <a href="{{ route('admin.pages.index') }}" title="Pages"
               class="flex items-center px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ $pagesOn ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                <svg class="w-5 h-5 flex-shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span x-show="sidebarOpen" x-transition.opacity class="ml-3 font-medium whitespace-nowrap">Pages</span>
            </a>
            <a href="{{ route('admin.contact-messages.index') }}" title="Contact messages"
               class="relative flex items-center px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ $msgOn ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-200' }}">
                <svg class="w-5 h-5 flex-shrink-0 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <span x-show="sidebarOpen" x-transition.opacity class="ml-3 font-medium whitespace-nowrap flex-1">Messages</span>
                @if($unreadMsgs > 0)
                    <span x-show="sidebarOpen" class="ml-2 px-1.5 min-w-5 text-center rounded-full bg-rose-500 text-white text-[10px] font-bold leading-5">{{ $unreadMsgs }}</span>
                    <span x-show="!sidebarOpen" x-cloak class="absolute top-1.5 right-2 h-2 w-2 rounded-full bg-rose-500"></span>
                @endif
            </a>
        </nav>

        <!-- Footer / Logged-in user info -->
        <div class="shrink-0 p-4 bg-slate-950 border-t border-slate-800 flex items-center">
            <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center font-bold text-white uppercase">
                {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
            </div>
            <div x-show="sidebarOpen" class="ml-3 overflow-hidden">
                <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name ?? 'Admin' }}</p>
                <p class="text-xs text-gray-400 truncate">{{ Auth::user()->roles->pluck('name')->first() ?? 'Admin' }}</p>
            </div>
        </div>
    </aside>
