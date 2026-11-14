@extends('layouts.admin')
@section('title', 'Edit Message')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Edit Message'])
    <form method="POST" action="{{ route('admin.custom-messages.update', $message) }}">
        @csrf @method('PUT')
        @include('admin.custom-messages._form')
    </form>
@endsection
