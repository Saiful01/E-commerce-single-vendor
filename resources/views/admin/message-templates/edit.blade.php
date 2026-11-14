@extends('layouts.admin')
@section('title', 'Edit Template')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Edit Template'])
    <form method="POST" action="{{ route('admin.message-templates.update', $template) }}">
        @csrf @method('PUT')
        @include('admin.message-templates._form')
    </form>
@endsection
