@extends('layouts.admin')

@section('title', 'Edit Role')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit role</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $role->name }}</p>
            </div>
            <a href="{{ route('admin.roles.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to roles</a>
        </div>

        @include('admin.roles._form', [
            'action' => route('admin.roles.update', $role),
            'method' => 'PUT',
            'role'   => $role,
        ])
    </div>
@endsection
