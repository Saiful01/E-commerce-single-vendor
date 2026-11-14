@extends('layouts.admin')
@section('title', 'Edit SMS Gateway')

@section('content')
    @include('admin.partials.page-header', ['title' => 'Edit SMS Gateway'])
    <form method="POST" action="{{ route('admin.sms-gateways.update', $gateway) }}">
        @csrf @method('PUT')
        @include('admin.sms-gateways._form')
    </form>
@endsection
