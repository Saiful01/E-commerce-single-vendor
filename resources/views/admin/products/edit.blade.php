@extends('layouts.admin')

@section('title', 'Edit Product')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit product</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $product->name }} <span class="font-mono text-slate-400">({{ $product->sku }})</span></p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to products</a>
        </div>

        @include('admin.products._form', [
            'action'  => route('admin.products.update', $product),
            'method'  => 'PUT',
            'product' => $product,
        ])
    </div>
@endsection
