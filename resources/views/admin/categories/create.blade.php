@extends('layouts.admin')

@section('title', 'Add Category')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add category</h2>
                <p class="text-xs text-slate-500 mt-1">Name, image, parent and search listing.</p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to categories</a>
        </div>

        @include('admin.categories._form', [
            'action'   => route('admin.categories.store'),
            'method'   => 'POST',
            'category' => null,
        ])
    </div>
@endsection
