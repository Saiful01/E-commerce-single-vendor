@extends('layouts.admin')
@section('title', 'Message Details')

@section('content')
    <a href="{{ route('admin.custom-messages.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back to messages</a>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm mt-4 p-6 max-w-3xl">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                @include('admin.partials.badge', ['value' => $message->channel])
                @include('admin.partials.badge', ['value' => $message->status])
            </div>
            <p class="text-xs text-gray-500">{{ $message->created_at->format('d M Y, h:i A') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-5 text-sm">
            <div><p class="text-xs text-gray-500">To</p><p class="font-medium text-gray-800">{{ $message->recipient }}</p></div>
            <div><p class="text-xs text-gray-500">Customer</p><p class="font-medium text-gray-800">{{ $message->user->name ?? '—' }}</p></div>
            <div><p class="text-xs text-gray-500">Created by</p><p class="font-medium text-gray-800">{{ $message->creator->name ?? '—' }}</p></div>
            <div><p class="text-xs text-gray-500">Sent at</p><p class="font-medium text-gray-800">{{ $message->sent_at?->format('d M Y, h:i A') ?? '—' }}</p></div>
        </div>

        @if($message->subject)
            <h3 class="mt-5 font-semibold text-gray-800">{{ $message->subject }}</h3>
        @endif
        <p class="mt-2 text-gray-600 whitespace-pre-line">{{ $message->body }}</p>

        @if($message->error)
            <div class="mt-5 p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700">{{ $message->error }}</div>
        @endif

        <div class="flex flex-wrap items-center gap-3 mt-6 pt-5 border-t border-gray-100">
            @if($message->status !== 'sent')
                <form method="POST" action="{{ route('admin.custom-messages.send', $message) }}">
                    @csrf
                    <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl">{{ $message->status === 'failed' ? 'Retry send' : 'Send now' }}</button>
                </form>
                <a href="{{ route('admin.custom-messages.edit', $message) }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-xl">Edit</a>
            @endif
            <div class="ml-auto">
                @include('admin.partials.delete-form', ['key' => 'msg-' . $message->id, 'action' => route('admin.custom-messages.destroy', $message)])
            </div>
        </div>
    </div>
@endsection
