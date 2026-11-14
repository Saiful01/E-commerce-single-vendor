@extends('layouts.admin')

@section('title', 'Edit Category')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit category</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $category->name }} <span class="font-mono text-slate-400">({{ $category->slug }})</span></p>
            </div>
            <a href="{{ route('admin.categories.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to categories</a>
        </div>

        @include('admin.categories._form', [
            'action'   => route('admin.categories.update', $category),
            'method'   => 'PUT',
            'category' => $category,
        ])
    </div>
@endsection
