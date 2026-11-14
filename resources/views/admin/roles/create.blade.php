@extends('layouts.admin')

@section('title', 'Create Role')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Create role</h2>
                <p class="text-xs text-slate-500 mt-1">Name the role and choose what it can access.</p>
            </div>
            <a href="{{ route('admin.roles.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to roles</a>
        </div>

        @include('admin.roles._form', [
            'action' => route('admin.roles.store'),
            'method' => 'POST',
            'role'   => null,
        ])
    </div>
@endsection
