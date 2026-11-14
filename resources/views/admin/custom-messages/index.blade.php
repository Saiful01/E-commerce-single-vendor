@extends('layouts.admin')
@section('title', 'Custom Messages')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Custom Messages', 'subtitle' => 'Customer-ke email ba SMS pathan.', 'actionUrl' => route('admin.custom-messages.create'), 'actionLabel' => 'New Message'])

    @include('admin.partials.tabs', ['tabs' => [
        ['label' => 'All', 'url' => route('admin.custom-messages.index'), 'active' => !$status],
        ['label' => 'Draft', 'url' => route('admin.custom-messages.index', ['status' => 'draft']), 'active' => $status === 'draft'],
        ['label' => 'Sent', 'url' => route('admin.custom-messages.index', ['status' => 'sent']), 'active' => $status === 'sent'],
        ['label' => 'Failed', 'url' => route('admin.custom-messages.index', ['status' => 'failed']), 'active' => $status === 'failed'],
    ]])

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="px-5 py-3 text-left">Recipient</th>
                <th class="px-5 py-3 text-left">Channel</th>
                <th class="px-5 py-3 text-left">Message</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Date</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($messages as $m)
                <tr class="hover:bg-gray-50 align-top">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-800">{{ $m->user->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $m->recipient }}</p>
                    </td>
                    <td class="px-5 py-3">@include('admin.partials.badge', ['value' => $m->channel])</td>
                    <td class="px-5 py-3 max-w-xs">
                        @if($m->subject)<p class="font-medium text-gray-800 truncate">{{ $m->subject }}</p>@endif
                        <p class="text-xs text-gray-500 truncate">{{ \Illuminate\Support\Str::limit($m->body, 70) }}</p>
                    </td>
                    <td class="px-5 py-3">@include('admin.partials.badge', ['value' => $m->status])</td>
                    <td class="px-5 py-3 text-gray-500">{{ ($m->sent_at ?? $m->created_at)->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap space-x-3">
                        <a href="{{ route('admin.custom-messages.show', $m) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                        @if($m->status !== 'sent')
                            <a href="{{ route('admin.custom-messages.edit', $m) }}" class="text-amber-600 hover:text-amber-800 font-medium">Edit</a>
                        @endif
                        @include('admin.partials.delete-form', ['key' => 'msg-' . $m->id, 'action' => route('admin.custom-messages.destroy', $m)])
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No messages yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $messages->links() }}</div>
@endsection
