@extends('layouts.admin')

@section('title', 'Message from ' . $message->name)

@section('content')
    @php $wa = preg_replace('/\D/', '', $message->phone); if (str_starts_with($wa, '0')) { $wa = '88' . $wa; } @endphp

    <div class="max-w-3xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-800">{{ $message->subject ?: 'Message' }}</h2>
            <a href="{{ route('admin.contact-messages.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to messages</a>
        </div>

        <section class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
            <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div><dt class="text-slate-400">From</dt><dd class="font-semibold text-slate-800">{{ $message->name }}@if($message->user) <span class="font-normal text-slate-400">(has an account)</span>@endif</dd></div>
                <div><dt class="text-slate-400">Phone</dt><dd class="font-semibold text-slate-800">{{ $message->phone }}</dd></div>
                <div><dt class="text-slate-400">Email</dt><dd class="font-semibold text-slate-800 break-all">{{ $message->email ?: 'Not given' }}</dd></div>
            </dl>

            <div class="rounded-xl bg-slate-50 border border-slate-200 p-4">
                <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ $message->message }}</p>
            </div>
            <p class="text-[11px] text-slate-400">Sent {{ $message->created_at->format('d M Y, h:i A') }}</p>

            <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                <a href="tel:{{ $message->phone }}" class="px-4 py-2.5 bg-indigo-600 text-white font-semibold text-xs rounded-xl hover:bg-indigo-700">Call</a>
                @if($wa)<a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="px-4 py-2.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">WhatsApp</a>@endif
                @if($message->email)<a href="mailto:{{ $message->email }}?subject=Re: {{ rawurlencode($message->subject ?: 'your message') }}" class="px-4 py-2.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">Email</a>@endif

                <form action="{{ route('admin.contact-messages.read', $message) }}" method="POST">@csrf @method('PATCH')
                    <button class="px-4 py-2.5 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">Mark as unread</button></form>
                <form action="{{ route('admin.contact-messages.destroy', $message) }}" method="POST" class="ml-auto" onsubmit="return confirm('Delete this message?')">@csrf @method('DELETE')
                    <button class="px-4 py-2.5 border border-rose-200 text-rose-600 font-semibold text-xs rounded-xl hover:bg-rose-50">Delete</button></form>
            </div>
        </section>
    </div>
@endsection
