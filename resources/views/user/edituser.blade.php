@extends('templates.layout')

@push('scripts')
    <script src="{{ asset('js/edituser.js') }}"></script>
@endpush

@section('content')

    <h3>Editing user <strong>{{Auth::user()->name}}</strong></h3>
    <div class="tab">
        <button class="tablinks" onclick="openTab(event, 'General')"  id="general_tab">General</button>
        <button class="tablinks" onclick="openTab(event, 'Password')" id="password_tab">Password</button>
        <button class="tablinks" onclick="openTab(event, 'Email')" id="email_tab">Email</button>
    </div>

    <div id="General" class="tabcontent">
        {{ Form::open(array('route' => 'editUserInfo')) }}
        @csrf
        <h2>General</h2>
        <div class="form-horizontal">
            <div class="form-group">
                <label for="name" class="col-sm-1 control-label text-left">Name</label>
                <div class="col-sm-6">
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{auth()->user()->name}}" required autofocus>

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
        </div> 
        <button class="btn btn-primary" type="submit">Submit Changes</button>
        {{ Form::close() }}   
    </div>

    <div id="Password" class="tabcontent">
        {{ Form::open(array('route' => 'editUserPassword')) }}
        @csrf
        <h2 class="d-inline-block">Password</h2>              
        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="mb-4 glyphicon glyphicon-question-sign" data-bs-toggle="tooltip" data-bs-placement="right" 
                title="Password must have 10 or more characters, <br>contain a lowercase letter, uppercase letter, number, and special character,
                    <br>must not contain any part of your name or email address,<br>and must not contain any character repeated more than 3 times.">
        </span>

        <div class="form-horizontal">
            <div class="form-group">
                <label for="old_password" class="col-sm-1 control-label text-left">Old Password</label>
                <div class="col-sm-6">
                    <input id="old_password" type="password" class="form-control @error('old_password') is-invalid @enderror" name="old_password" required autofocus>
                    @error('old_password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="password" class="col-sm-1 control-label text-left">New Password</label>
                <div class="col-sm-6">
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autofocus>
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="password-confirm" class="col-sm-1 control-label text-left">Confirm New Password</label>
                <div class="col-sm-6">
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autofocus>
                </div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Submit Changes</button>
        {{ Form::close() }} 
    </div>

    <div id="Email" class="tabcontent">
        {{ Form::open(array('route' => 'editUserEmail')) }}
        @csrf
        <h2>Email</h2>
        <div class="form-horizontal">
            <div class="form-group">
                <label for="old-email" class="col-sm-1 control-label text-left">Current Email</label>
                <div class="col-sm-6">
                    <input id="old-email" type="text" class="form-control" name="oldemail" value="{{auth()->user()->email}}" disabled>
                </div>
            </div>
            <div class="form-group">
                <label for="email" class="col-sm-1 control-label text-left">New Email</label>
                <div class="col-sm-6">
                    <input id="email" type="text" class="form-control @error('email') is-invalid @enderror" name="email" value="" required autofocus>
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="email-confirm" class="col-sm-1 control-label text-left">Confirm New Email</label>
                <div class="col-sm-6">
                    <input id="email-confirm" type="text" class="form-control" name="email_confirmation" value="" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label for="emailpassword" class="col-sm-1 control-label text-left">Password</label>
                <div class="col-sm-6">
                    <input id="emailpassword" type="password" class="form-control @error('emailpassword') is-invalid @enderror" name="emailpassword" value="" required autofocus>
                    @error('emailpassword')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Submit Changes</button>
        {{ Form::close() }} 
    </div>
        
    <a href="{{ url()->previous() }}" class="btn btn-primary mt-3">Back</a>
@endsection
