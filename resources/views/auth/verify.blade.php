@extends('templates.layout')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Verify Your Email Address') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('A fresh verification link has been sent to your email address.') }}
                        </div>
                    @endif

                    {{ __('Please check your email for a verification link. Allow a few minutes for it to arrive.') }} <br>
                    {{ __('If you do not see the message, please check your \'spam\', \'junk\' or \'deleted\' folder. ') }} <br>
                    {{ __('If you did not receive the email') }}, <a href="{{ route('verification.resend') }}">{{ __('click here to request another') }}</a>. <br><br>
                    {{ __('If you did not receive the message, try adding access@tlcmap.org to your email \'white list\'. If you still do not get the message please contact tlcmap@newcastle.edu.au ') }} 
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
