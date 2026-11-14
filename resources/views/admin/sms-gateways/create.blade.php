@extends('layouts.admin')
@section('title', 'New SMS Gateway')

@section('content')
    @include('admin.partials.page-header', ['title' => 'New SMS Gateway'])
    <form method="POST" action="{{ route('admin.sms-gateways.store') }}">
        @csrf
        @include('admin.sms-gateways._form')
    </form>
@endsection
