@extends('layouts.auth')

@section('title', 'Verify email')
@section('heading', 'Check your inbox')
@section('subheading', 'We sent a verification link to your email. Click it to finish setting up your account.')

@section('content')
    @if(session('status') == 'verification-link-sent')
        <div class="mb-5 rounded-xl bg-green-50 p-3.5 text-sm text-green-800" role="status">A new verification link has been sent to your email.</div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <button class="btn btn-primary w-full !py-3.5 text-base" :disabled="busy"><i class="bi bi-envelope-arrow-up"></i> Resend verification email</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full rounded-xl py-3 text-sm font-semibold text-neutral-600 hover:bg-neutral-50">Sign out</button>
        </form>
    </div>
@endsection
