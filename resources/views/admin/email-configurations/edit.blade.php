@extends('layouts.admin')
@section('title', 'Edit Email Configuration')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Edit Email Configuration'])
    <form method="POST" action="{{ route('admin.email-configurations.update', $config) }}">
        @csrf @method('PUT')
        @include('admin.email-configurations._form')
    </form>
@endsection
