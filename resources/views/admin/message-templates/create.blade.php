@extends('layouts.admin')
@section('title', 'New Template')

@section('content')
    @include('admin.partials.page-header', ['title' => 'New Template'])
    <form method="POST" action="{{ route('admin.message-templates.store') }}">
        @csrf
        @include('admin.message-templates._form')
    </form>
@endsection
