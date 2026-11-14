@extends('layouts.auth')

@section('title', 'Confirm password')
@section('heading', 'Confirm your password')
@section('subheading', 'This is a secure area. Please confirm your password before continuing.')

@section('content')
    <form method="POST" action="{{ route('password.confirm') }}" x-data="{ busy: false }" @submit="busy = true" class="space-y-4">
        @csrf
        <x-auth-field name="password" type="password" label="Password" icon="bi-lock" autocomplete="current-password" required autofocus />
        <button class="btn btn-primary w-full !py-3.5 text-base" :disabled="busy"><i class="bi bi-arrow-repeat animate-spin" x-show="busy" x-cloak></i> <span x-text="busy ? 'Checking...' : 'Confirm'"></span></button>
    </form>
@endsection
