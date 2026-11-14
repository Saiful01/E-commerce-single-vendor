@extends('layouts.admin')
@section('title', 'SMS Gateways')

@section('content')
    @include('admin.partials.page-header', ['title' => 'SMS Gateways', 'subtitle' => 'Ekbar-e ekta gateway active thakbe.', 'actionUrl' => route('admin.sms-gateways.create'), 'actionLabel' => 'New Gateway'])

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="px-5 py-3 text-left">Name</th>
                <th class="px-5 py-3 text-left">API URL</th>
                <th class="px-5 py-3 text-left">Sender ID</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($gateways as $g)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $g->name }}</td>
                    <td class="px-5 py-3 text-gray-500 max-w-xs truncate"><span class="text-xs font-semibold text-gray-400">{{ $g->http_method }}</span> {{ $g->api_url }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $g->sender_id ?? '—' }}</td>
                    <td class="px-5 py-3">@include('admin.partials.badge', ['value' => $g->is_active ? 'active' : 'inactive'])</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap space-x-3">
                        @if(!$g->is_active)
                            <form method="POST" action="{{ route('admin.sms-gateways.activate', $g) }}" class="inline">
                                @csrf @method('PATCH')
                                <button class="text-green-600 hover:text-green-800 font-medium">Set active</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.sms-gateways.test', $g) }}" class="inline-flex items-center gap-1">
                                @csrf
                                <input type="text" name="test_number" placeholder="01XXXXXXXXX" required class="w-32 rounded-lg border-gray-300 text-xs py-1">
                                <button class="text-sky-600 hover:text-sky-800 font-medium">Test</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.sms-gateways.edit', $g) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
                        @include('admin.partials.delete-form', ['key' => 'sms-' . $g->id, 'action' => route('admin.sms-gateways.destroy', $g)])
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No SMS gateway yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $gateways->links() }}</div>
@endsection
