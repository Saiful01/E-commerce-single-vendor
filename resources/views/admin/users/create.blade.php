@extends('layouts.admin')

@section('title', 'Add User')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add user</h2>
                <p class="text-xs text-slate-500 mt-1">Account details, access and saved addresses.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to users</a>
        </div>

        @include('admin.users._form', [
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'user'   => null,
        ])
    </div>
@endsection
