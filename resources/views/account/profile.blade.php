@extends('layouts.account')
@section('page_title', 'Profile')

@php $input = 'w-full rounded-xl border border-black/15 bg-white px-3.5 py-3 text-sm focus:border-[var(--primary)] focus:outline-none'; @endphp

@section('account')
<form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4 rounded-2xl bg-white p-5 md:p-6">
    @csrf @method('PATCH')
    <h1 class="font-display text-2xl font-extrabold">Profile</h1>
    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block text-sm font-medium">Full name
            <input name="name" value="{{ old('name', $user->name) }}" required class="{{ $input }} mt-1.5">
            @error('name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
        <label class="block text-sm font-medium">Mobile number
            <input name="phone" value="{{ old('phone', $user->phone) }}" required inputmode="tel" class="{{ $input }} mt-1.5">
            @error('phone')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
        <label class="block text-sm font-medium sm:col-span-2">Email
            <input value="{{ $user->email }}" disabled class="{{ $input }} mt-1.5 bg-neutral-50 text-neutral-500">
            <span class="mt-1 block text-xs font-normal text-neutral-500">Email is used to sign in, so it can be changed from the account settings page.</span></label>
    </div>
    <button class="btn btn-primary">Save changes</button>
</form>

{{-- Uses Breeze's existing password route --}}
<form method="POST" action="{{ route('password.update') }}" class="space-y-4 rounded-2xl bg-white p-5 md:p-6">
    @csrf @method('PUT')
    <h2 class="font-display text-xl font-bold">Change password</h2>
    @if($errors->updatePassword->any())<ul class="rounded-lg bg-red-50 p-3 text-sm text-red-700">@foreach($errors->updatePassword->all() as $e)<li>{{ $e }}</li>@endforeach</ul>@endif
    <div class="grid gap-4 sm:grid-cols-3">
        <label class="block text-sm font-medium">Current password<input type="password" name="current_password" autocomplete="current-password" class="{{ $input }} mt-1.5"></label>
        <label class="block text-sm font-medium">New password<input type="password" name="password" autocomplete="new-password" class="{{ $input }} mt-1.5"></label>
        <label class="block text-sm font-medium">Confirm new password<input type="password" name="password_confirmation" autocomplete="new-password" class="{{ $input }} mt-1.5"></label>
    </div>
    <button class="btn btn-ghost">Update password</button>
</form>
@endsection
