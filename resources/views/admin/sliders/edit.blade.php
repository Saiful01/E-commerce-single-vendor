@extends('layouts.admin')

@section('title', 'Edit Banner')

@section('content')
    <div class="max-w-6xl mx-auto space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Edit banner</h2>
                <p class="text-xs text-slate-500 mt-1">{{ $slider->title }} <span class="text-slate-400">({{ $slider->position_label }})</span></p>
            </div>
            <a href="{{ route('admin.sliders.index', ['position' => $slider->position]) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">&larr; Back to banners</a>
        </div>

        @include('admin.sliders._form', [
            'action' => route('admin.sliders.update', $slider),
            'method' => 'PUT',
            'slider' => $slider,
        ])
    </div>
@endsection
