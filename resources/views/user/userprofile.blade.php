@extends('templates.layout')

@section('content')
    <p class="h2">{{ Auth::user()->name }}'s Profile</p>
    <div class="content">
        <div class="m-1">
            <span class="h3">Your Details</span>
            <a href="{{route('editUserPage')}}" class="ml-4 btn btn-primary">Edit</a>
        </div>
        <table class="table">
            <tr><td>Name</td><td>{{ Auth::user()->name }}</td></tr>
            <tr><td>Email</td><td>{{ Auth::user()->email }}</td></tr>
            <tr><td>Role</td><td>{{ Auth::user()->roles[0]['name'] }}</td></tr>
            <tr><td>Verified</td><td>@if(Auth::user()->email_verified_at !== null)<span class="text-success">Email has been verified</span> @else<span class="text-danger">Email has not been verified</span> <a href="{{ route('verification.resend') }}">{{ __('click here to request another') }}</a>. @endif</td></tr>
            <tr><td>Created</td><td>{{ Auth::user()->created_at }}</td><td></td></tr>
            <tr><td>Updated</td><td>{{ Auth::user()->updated_at }}</td><td></td></tr>
        </table>
        <div class="ml-4 btn-group">
            <div class="row"><a href="{{url('myprofile/mydatasets')}}" class="mb-3 btn btn-primary btn-block">My Layers</a></div>
            <div class="row"> <a href="{{url('myprofile/mysearches')}}" class="mb-3 btn btn-primary btn-block">My Searches</a></div>
        </div>
    </div>  
@endsection