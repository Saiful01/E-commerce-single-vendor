@extends('layouts.auth')

@section('title', 'Forgot password')
@section('aside_title', 'Locked out? It happens to everyone')
@section('heading', 'Reset your password')
@section('subheading', 'Enter the email you registered with and we will send you a link to choose a new password.')

@section('content')
    @if(session('status'))
        <div class="mb-5 rounded-xl bg-green-50 p-3.5 text-sm text-green-800" role="status"><i class="bi bi-check-circle-fill me-1"></i> {{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" x-data="{ busy: false }" @submit="busy = true" class="space-y-4">
        @csrf
        <x-auth-field name="email" type="email" label="Email" icon="bi-envelope" autocomplete="email" required autofocus />
        <button class="btn btn-primary w-full !py-3.5 text-base" :disabled="busy"><i class="bi bi-arrow-repeat animate-spin" x-show="busy" x-cloak></i> <span x-text="busy ? 'Sending...' : 'Send reset link'"></span></button>
    </form>
@endsection

@section('footer')
    <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline"><i class="bi bi-arrow-left"></i> Back to sign in</a>
@endsection
