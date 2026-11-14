@extends('layouts.admin')

@section('title', 'Contact messages')

@section('content')
    @php
        $tabBase = 'px-3.5 py-2 text-xs font-semibold rounded-xl border whitespace-nowrap transition-colors flex items-center gap-2';
        $tabOn   = 'bg-indigo-600 text-white border-indigo-600 shadow-sm';
        $tabOff  = 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600';
    @endphp

    <div class="space-y-5">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Contact messages</h2>
            <p class="text-xs text-slate-500 mt-1">Messages from the contact form on your shop.</p>
        </div>

        <nav class="flex gap-2" aria-label="Filter">
            <a href="{{ route('admin.contact-messages.index', array_filter(['q' => $q])) }}" class="{{ $tabBase }} {{ $filter === 'all' ? $tabOn : $tabOff }}">All <span class="opacity-70">{{ $total }}</span></a>
            <a href="{{ route('admin.contact-messages.index', array_filter(['q' => $q, 'filter' => 'unread'])) }}" class="{{ $tabBase }} {{ $filter === 'unread' ? $tabOn : $tabOff }}">Unread <span class="opacity-70">{{ $unread }}</span></a>
        </nav>

        <form method="GET" class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs">
            @if($filter === 'unread')<input type="hidden" name="filter" value="unread">@endif
            <input type="search" name="q" value="{{ $q }}" placeholder="Search name, phone, email or message" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
        </form>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden divide-y divide-slate-100">
            @forelse ($messages as $m)
                <a href="{{ route('admin.contact-messages.show', $m) }}" class="flex items-start gap-3 px-5 py-4 hover:bg-slate-50/60 transition-colors">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $m->is_read ? 'bg-transparent' : 'bg-indigo-600' }}" aria-label="{{ $m->is_read ? 'Read' : 'Unread' }}"></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs {{ $m->is_read ? 'font-semibold text-slate-700' : 'font-bold text-slate-900' }} truncate">{{ $m->name }} <span class="font-normal text-slate-400">· {{ $m->phone }}</span></p>
                            <span class="text-[11px] text-slate-400 shrink-0">{{ $m->created_at->diffForHumans() }}</span>
                        </div>
                        @if($m->subject)<p class="text-xs font-semibold text-slate-600 mt-0.5 truncate">{{ $m->subject }}</p>@endif
                        <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $m->message }}</p>
                    </div>
                </a>
            @empty
                <p class="text-center py-14 text-xs text-slate-400">{{ $filter === 'unread' ? 'No unread messages.' : 'No messages yet.' }}</p>
            @endforelse
        </div>

        {{ $messages->links() }}
    </div>
@endsection
