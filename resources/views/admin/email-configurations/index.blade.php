@extends('layouts.admin')
@section('title', 'Email Configurations')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Email Configurations', 'subtitle' => 'Multiple SMTP rakha jabe, kintu ekbar-e ekta active thakbe.', 'actionUrl' => route('admin.email-configurations.create'), 'actionLabel' => 'New Configuration'])

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="px-5 py-3 text-left">Name</th>
                <th class="px-5 py-3 text-left">Host</th>
                <th class="px-5 py-3 text-left">From</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($configs as $c)
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $c->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $c->host }}:{{ $c->port }} <span class="text-xs text-gray-400 uppercase">{{ $c->encryption }}</span></td>
                    <td class="px-5 py-3 text-gray-600">{{ $c->from_name }}<br><span class="text-xs text-gray-400">{{ $c->from_address }}</span></td>
                    <td class="px-5 py-3">@include('admin.partials.badge', ['value' => $c->is_active ? 'active' : 'inactive'])</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap space-x-3">
                        @if(!$c->is_active)
                            <form method="POST" action="{{ route('admin.email-configurations.activate', $c) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="text-green-600 hover:text-green-800 font-medium">Set active</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.email-configurations.test', $c) }}" class="inline-flex items-center gap-1">
                                @csrf
                                <input type="email" name="test_email" placeholder="test@email.com" required class="w-36 rounded-lg border-gray-300 text-xs py-1">
                                <button class="text-sky-600 hover:text-sky-800 font-medium">Test</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.email-configurations.edit', $c) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
                        @include('admin.partials.delete-form', ['key' => 'mail-' . $c->id, 'action' => route('admin.email-configurations.destroy', $c)])
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No email configuration yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $configs->links() }}</div>
@endsection
