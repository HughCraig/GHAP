@extends('templates.layout')

@section('content')
    <h2>Welcome to the admin page!</h2>
    <a href="{{ url('/') }}" class="mb-3 btn btn-primary">Back</a><br>
    <br><a href="{{ url('/admin/users') }}">Manage Users</a>
    <br><a href="#">Something else</a>
    <br><a href="#">etc</a>
@endsection
