@extends('layouts.admin')
@section('title', 'New Email Configuration')

@section('content')
    @include('admin.partials.page-header', ['title' => 'New Email Configuration'])
    <form method="POST" action="{{ route('admin.email-configurations.store') }}">
        @csrf
        @include('admin.email-configurations._form')
    </form>
@endsection
