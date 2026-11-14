@extends('layouts.admin')
@section('title', 'New Message')

@section('content')
    @include('admin.partials.page-header', ['title' => 'New Message'])
    <form method="POST" action="{{ route('admin.custom-messages.store') }}">
        @csrf
        @include('admin.custom-messages._form')
    </form>
@endsection
