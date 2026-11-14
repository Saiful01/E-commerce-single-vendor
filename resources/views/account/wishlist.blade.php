@extends('layouts.account')
@section('page_title', 'Wishlist')

@section('account')
<div class="rounded-2xl bg-white p-5 md:p-6">
    <h1 class="font-display text-2xl font-extrabold">Wishlist</h1>
    <p class="text-sm text-neutral-500">Tap the heart again to remove an item.</p>
</div>

@if($products->isEmpty())
    <div class="rounded-2xl bg-white px-6 py-14 text-center"><i class="bi bi-heart text-5xl text-neutral-300"></i>
        <p class="mt-3 font-semibold">Nothing saved yet</p><a href="{{ url('/shop') }}" class="btn btn-primary mt-5">Browse products</a></div>
@else
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5">
        @foreach($products as $product)
            <div x-data x-show="$store.wish.has({{ $product->id }})" x-transition>@include('partials.product-card', ['product' => $product])</div>
        @endforeach
    </div>
@endif
@endsection
