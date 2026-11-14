@php
    $settings = $siteSettings ?? \App\Models\SiteSetting::current();

    $userCan = fn ($permission) => ! $permission || auth()->user()?->can($permission);

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR MENU CONFIG
    | - Single link  : label, icon, route, match, can
    | - Group (dropdown): label, icon, children[ label, route, match, can ]
    | Notun menu add korte shudhu ekhane ekta item add koro.
    |--------------------------------------------------------------------------
    */
    $menu = [
        [
            'label' => 'Dashboard',
            'icon'  => ['M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            'route' => 'admin.dashboard',
            'match' => 'admin.dashboard',
            'can'   => null,
        ],

        [
            'label' => 'User Management',
            'icon'  => ['M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            'children' => [
                ['label' => 'Users',               'route' => 'admin.users.index', 'match' => 'admin.users.*', 'can' => 'manage-users'],
                ['label' => 'Roles & Permissions', 'route' => 'admin.roles.index', 'match' => 'admin.roles.*', 'can' => 'manage-roles'],
            ],
        ],

        [
            'label' => 'Catalog',
            'icon'  => ['M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            'children' => [
                ['label' => 'Products',   'route' => 'admin.products.index',   'match' => 'admin.products.*',   'can' => 'manage-products'],
                ['label' => 'Categories', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'can' => 'manage-categories'],
                ['label' => 'Brands',     'route' => 'admin.brands.index',     'match' => 'admin.brands.*',     'can' => 'manage-brands'],
            ],
        ],

        [
            'label' => 'Sales',
            'icon'  => ['M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            'children' => [
                ['label' => 'Orders',           'route' => 'admin.orders.index',          'match' => 'admin.orders.*',          'can' => 'manage-orders'],
                ['label' => 'Order Statuses',   'route' => 'admin.order-statuses.index',  'match' => 'admin.order-statuses.*',  'can' => 'order-statuses'],
                ['label' => 'Abandoned Carts',  'route' => 'admin.abandoned-carts.index', 'match' => 'admin.abandoned-carts.*', 'can' => 'manage-abandoned-carts'],
            ],
        ],

        [
            'label' => 'Shipping & Payment',
            'icon'  => ['M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h1m6 0h1v-5l-3-4h-4v9m0 0h-1M7 19a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z'],
            'children' => [
                ['label' => 'Courier Services',  'route' => 'admin.courier-services.index',  'match' => 'admin.courier-services.*',  'can' => 'manage-courier-services'],
                ['label' => 'Payment Gateways',  'route' => 'admin.payment-gateways.index',  'match' => 'admin.payment-gateways.*',  'can' => 'manage-payment-gateways'],
            ],
        ],

        [
            'label' => 'Customer Engagement',
            'icon'  => ['M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
            'children' => [
                ['label' => 'Wishlists',        'route' => 'admin.wishlists.index',       'match' => 'admin.wishlists.*',       'can' => 'manage-wishlists'],
                ['label' => 'Product Reviews',  'route' => 'admin.product-reviews.index', 'match' => 'admin.product-reviews.*', 'can' => 'manage-product-reviews'],
            ],
        ],

        [
            'label' => 'Marketing',
            'icon'  => ['M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z'],
            'children' => [
                ['label' => 'Coupons',            'route' => 'admin.coupons.index', 'match' => 'admin.coupons.*', 'can' => 'manage-coupons'],
                ['label' => 'Banners & Sliders',  'route' => 'admin.sliders.index', 'match' => 'admin.sliders.*', 'can' => 'manage-sliders'],
            ],
        ],

        [
            'label' => 'Communication',
            'icon'  => ['M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
            'children' => [
                ['label' => 'Custom Messages',      'route' => 'admin.custom-messages.index',    'match' => 'admin.custom-messages.*',    'can' => 'manage-custom-messages'],
                ['label' => 'SMS/Email Templates',  'route' => 'admin.message-templates.index',  'match' => 'admin.message-templates.*',  'can' => 'manage-sms-email-templates'],
            ],
        ],
        [
    'label' => 'Pages & Contact',
    'icon'  => [
        'M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h7l7 7v9a2 2 0 01-2 2z',
        'M14 3v7h7',
    ],
    'children' => [
        [
            'label' => 'Pages',
            'route' => 'admin.pages.index',
            'match' => 'admin.pages.*',
            'can'   => 'manage-pages',
        ],
        [
            'label' => 'Contact Messages',
            'route' => 'admin.contact-messages.index',
            'match' => 'admin.contact-messages.*',
            'can'   => 'manage-contact-messages',
        ],
    ],
],

        [
            'label' => 'Settings',
            'icon'  => [
                'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
            ],
            'children' => [
                ['label' => 'Site Settings',          'route' => 'admin.settings.edit',             'match' => 'admin.settings.*',             'can' => 'manage-settings'],
                ['label' => 'Email Configurations',   'route' => 'admin.email-configurations.index','match' => 'admin.email-configurations.*', 'can' => 'manage-email-configurations'],
                ['label' => 'SMS Gateways',           'route' => 'admin.sms-gateways.index',        'match' => 'admin.sms-gateways.*',         'can' => 'manage-sms-gateways'],
            ],
        ],
    ];
@endphp
    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $settings->site_name }} - Admin Dashboard</title>

    @if ($settings->favicon_url)
        <link rel="icon" href="{{ $settings->favicon_url }}">
    @endif

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- SweetAlert2 CSS & JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts and Styles (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none !important}</style>
</head>
<body class="font-sans antialiased bg-gray-100" x-data="{ sidebarOpen: true }">
<div class="min-h-screen flex">

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
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Header Bar -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">
                @yield('title', 'Dashboard')
            </h2>

            <!-- User Dropdown (Alpine.js) -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 focus:outline-none">
                    <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name ?? 'Admin' }}</span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 border border-gray-100 z-50">
                    <!-- User info -->
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ Auth::user()->name ?? 'Admin' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email ?? '' }}</p>
                    </div>

                    <!-- Profile -->
                    <a href="{{ route('profile.edit') }}"
                       class="w-full text-left px-4 py-2 text-sm flex items-center gap-2 hover:bg-gray-50 {{ request()->routeIs('profile.*') ? 'text-indigo-600 font-medium' : 'text-gray-700' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Profile
                    </a>

                    <div class="border-t border-gray-100 my-1"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Page Body Content -->
        <main class="p-6 flex-1">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>


<!-- SweetAlert2 Flash Messages & Delete Confirmation -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Flash Success Alert
        @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: @json(session('success')),
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        @endif

        // Flash Error Alert
        @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: @json(session('error')),
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
        @endif
    });

    // Global Delete Confirmation Function
    function confirmDelete(formId, message = 'You won\'t be able to revert this!') {
        Swal.fire({
            title: 'Are you sure?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5', // Indigo-600
            cancelButtonColor: '#f43f5e',  // Rose-500
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl text-xs font-semibold px-4 py-2',
                cancelButton: 'rounded-xl text-xs font-semibold px-4 py-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
</script>

</body>
</html>
