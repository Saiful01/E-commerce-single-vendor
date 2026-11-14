@extends('layouts.admin')

@section('title', 'Pages')

@section('content')
    <div class="space-y-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Pages</h2>
                <p class="text-xs text-slate-500 mt-1">About us, terms, privacy, return policy, FAQ, contact and any other page of your shop.</p>
            </div>
            <a href="{{ route('admin.pages.create') }}"
               class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New page
            </a>
        </div>

        <form method="GET" class="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search by title or address" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
        </form>

        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-6">Page</th>
                        <th class="py-3.5 px-6">Type</th>
                        <th class="py-3.5 px-6">Shown in</th>
                        <th class="py-3.5 px-6">Status</th>
                        <th class="py-3.5 px-6">Updated</th>
                        <th class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs font-medium">
                    @forelse ($pages as $page)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 px-6">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="font-bold text-slate-800 hover:text-indigo-600">{{ $page->title }}</a>
                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">/pages/{{ $page->slug }}@if($page->is_system) <span class="ml-1 px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-sans">Required</span>@endif</p>
                            </td>
                            <td class="py-3.5 px-6 text-slate-600">{{ $page->template_label }}</td>
                            <td class="py-3.5 px-6 text-slate-600">
                                @if($page->show_in_footer)<span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-[10px] font-bold text-slate-600 mr-1">Footer</span>@endif
                                @if($page->show_in_header)<span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-[10px] font-bold text-slate-600">Top menu</span>@endif
                                @if(! $page->show_in_footer && ! $page->show_in_header)<span class="text-slate-400">Link only</span>@endif
                            </td>
                            <td class="py-3.5 px-6">
                                <form action="{{ route('admin.pages.toggle', $page) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" role="switch" aria-checked="{{ $page->is_active ? 'true' : 'false' }}" aria-label="Show or hide {{ $page->title }}"
                                            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $page->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}">
                                        <span class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform {{ $page->is_active ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                                    </button>
                                </form>
                            </td>
                            <td class="py-3.5 px-6 text-slate-400">{{ $page->updated_at->format('d M Y') }}</td>
                            <td class="py-3.5 px-6 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if($page->is_active)<a href="{{ $page->url }}" target="_blank" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg">View</a>@endif
                                    <a href="{{ route('admin.pages.edit', $page) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-slate-100 rounded-lg">Edit</a>
                                    @unless($page->is_system)
                                        <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($page->title) }}? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg">Delete</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-14 text-slate-400">No pages yet. <a href="{{ route('admin.pages.create') }}" class="text-indigo-600 font-semibold">Create the first one</a></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">{{ $pages->links() }}</div>
        </div>
    </div>
@endsection
