@extends('layouts.admin')
@section('title', 'SMS / Email Templates')

@section('content')
    @include('admin.partials.page-header', ['title' => 'SMS / Email Templates', 'subtitle' => 'Reusable message template gulo manage korun.', 'actionUrl' => route('admin.message-templates.create'), 'actionLabel' => 'New Template'])

    @include('admin.partials.tabs', ['tabs' => [
        ['label' => 'All', 'url' => route('admin.message-templates.index'), 'active' => !$channel],
        ['label' => 'Email', 'url' => route('admin.message-templates.index', ['channel' => 'email']), 'active' => $channel === 'email'],
        ['label' => 'SMS', 'url' => route('admin.message-templates.index', ['channel' => 'sms']), 'active' => $channel === 'sms'],
    ]])

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="px-5 py-3 text-left">Name</th>
                <th class="px-5 py-3 text-left">Slug</th>
                <th class="px-5 py-3 text-left">Channel</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($templates as $t)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-medium text-gray-800">{{ $t->name }}</td>
                    <td class="px-5 py-3 text-gray-500"><code>{{ $t->slug }}</code></td>
                    <td class="px-5 py-3">@include('admin.partials.badge', ['value' => $t->channel])</td>
                    <td class="px-5 py-3">@include('admin.partials.badge', ['value' => $t->is_active ? 'active' : 'inactive'])</td>
                    <td class="px-5 py-3 text-right space-x-3 whitespace-nowrap">
                        <a href="{{ route('admin.message-templates.edit', $t) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>
                        @include('admin.partials.delete-form', ['key' => 'tpl-' . $t->id, 'action' => route('admin.message-templates.destroy', $t)])
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No templates yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $templates->links() }}</div>
@endsection
