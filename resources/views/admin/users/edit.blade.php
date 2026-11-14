@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit user</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $user->name }} <span class="text-slate-400">({{ $user->email }})</span></p>
            </div>
            <a href="{{ route('admin.users.index', ['type' => $user->user_type]) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to users</a>
        </div>

        @include('admin.users._form', [
            'action' => route('admin.users.update', $user),
            'method' => 'PUT',
            'user'   => $user,
        ])
    </div>
@endsection
