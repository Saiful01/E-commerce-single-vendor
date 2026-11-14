<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">{{ $title }}</h1>
        @if(!empty($subtitle))<p class="text-sm text-gray-500 mt-0.5">{{ $subtitle }}</p>@endif
    </div>
    @if(!empty($actionUrl))
        <a href="{{ $actionUrl }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/30 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ $actionLabel ?? 'Add New' }}
        </a>
    @endif
</div>
