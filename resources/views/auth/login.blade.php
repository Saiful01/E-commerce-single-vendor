@extends('layouts.auth')

@section('title', 'Sign in')
@section('heading', 'Welcome back')
@section('subheading', 'Sign in to track orders, save addresses and check out faster.')

@section('content')
    @if(session('status'))
        <div class="mb-5 rounded-xl bg-green-50 p-3.5 text-sm text-green-800" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ busy: false }" @submit="busy = true" class="space-y-4">
        @csrf
        <x-auth-field name="login" label="Email or mobile number" icon="bi-person" autocomplete="username" placeholder="you@example.com or 01XXXXXXXXX" required autofocus />
        <x-auth-field name="password" type="password" label="Password" icon="bi-lock" autocomplete="current-password" required />

        <div class="flex items-center justify-between text-sm">
            <label class="inline-flex items-center gap-2"><input type="checkbox" name="remember" class="h-4 w-4 rounded accent-[var(--primary)]"> Remember me</label>
            @if(Route::has('password.request'))<a href="{{ route('password.request') }}" class="font-semibold text-brand hover:underline">Forgot password?</a>@endif
        </div>

        <button class="btn btn-primary w-full !py-3.5 text-base" :disabled="busy"><i class="bi bi-arrow-repeat animate-spin" x-show="busy" x-cloak></i> <span x-text="busy ? 'Signing in...' : 'Sign in'"></span></button>
    </form>
@endsection

@section('footer')
    New to {{ $setting->site_name ?: config('app.name') }}? <a href="{{ route('register') }}" class="font-semibold text-brand hover:underline">Create an account</a>
@endsection
