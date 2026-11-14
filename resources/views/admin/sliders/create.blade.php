@extends('layouts.admin')

@section('title', 'Add Banner')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Add banner</h2>
                <p class="text-xs text-slate-500 mt-1">Choose where it appears, upload the image and set the link.</p>
            </div>
            <a href="{{ route('admin.sliders.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to banners</a>
        </div>

        @include('admin.sliders._form', [
            'action' => route('admin.sliders.store'),
            'method' => 'POST',
            'slider' => null,
        ])
    </div>
@endsection
