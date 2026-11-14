@extends('layouts.auth')

@section('title', 'Choose a new password')
@section('aside_title', 'Almost there')
@section('heading', 'Choose a new password')
@section('subheading', 'Pick something you have not used on other sites.')

@section('content')
    <form method="POST" action="{{ route('password.store') }}" x-data="{ busy: false }" @submit="busy = true" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-auth-field name="email" type="email" label="Email" icon="bi-envelope" :value="$request->email" autocomplete="username" required autofocus />
        <x-auth-field name="password" type="password" label="New password" icon="bi-lock" autocomplete="new-password" hint="Use at least 8 characters." required />
        <x-auth-field name="password_confirmation" type="password" label="Confirm new password" icon="bi-shield-lock" autocomplete="new-password" required />
        <button class="btn btn-primary w-full !py-3.5 text-base" :disabled="busy"><i class="bi bi-arrow-repeat animate-spin" x-show="busy" x-cloak></i> <span x-text="busy ? 'Saving...' : 'Reset password'"></span></button>
    </form>
@endsection
