@extends('templates.layout')

@push('scripts')
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            url = "{{ url()->full() }}";
            userId = "{{ $user->id }}";
        </script>

    <script src="{{ asset('js/viewuser.js') }}"></script>
@endpush

@section('content')
    <h2>User Management</h2>
    <div class="alert alert-success mb-1" style="display: none;"></div>
    <a href="{{ url('/admin/users') }}" class="mb-3 btn btn-primary">Back</a><br>
    <table class="table table-striped">
        <tr><td>Email</td><td>{{ $user->email }}</td><td></td></tr>
        <tr><td>Name</td><td>{{ $user->name }}</td><td></td></tr>
        <tr><td>Created</td><td>{{ $user->created_at }}</td><td></td></tr>
        <tr><td>Updated</td><td>{{ $user->updated_at }}</td><td></td></tr>
        <tr><td>Role</td><td>{{ $user->roles[0]['name'] }}</td><td>
        @if($user->roles[0]['name'] != 'SUPER_ADMIN')
            <form method="POST" action="{{ url()->full() }}/updateUserRole">
                @csrf
                <select name="selectRole" id="selectRole" data-role="{{ $user->roles[0]['name'] }}">
                    <option value="REGULAR">REGULAR</option>
                    <option value="ADMIN">ADMIN</option>
                    <option value="LOCKED">LOCKED</option>
                </select>
                <button type="submit">Update Role</button>
            </form>
        </td></tr>
        @endif
        <tr>
            <td>Active</td>
            <form method="POST" action="{{ url()->full() }}/activateDeactivateUser">
                @csrf
                @if($user->is_active)
                    <td class="text-success">Active</td>
                    @if($user->roles[0]['name'] !== 'SUPER_ADMIN')
                    <td><button type="Submit">Deactivate</button></td>
                    @endif
                @else
                    <td class="text-danger">Inactive</td>
                    @if($user->roles[0]['name'] !== 'SUPER_ADMIN')
                    <td><button type="Submit">Activate</button></td>
                    @endif
                @endif 
            </form>
        </tr>

        <tr>
            <td>Verified</td>
            <form method="POST" action="{{ url()->full() }}/setEmailAsVerified">
                @csrf
                @if($user->email_verified_at != null)
                    <td class="text-success">Email has been verified</td>
                @else
                    <td class="text-danger">Email has not been verified</td>
                    @if($user->roles[0]['name'] !== 'SUPER_ADMIN')
                    <td><button type="Submit">Set To Verified</button></td>
                    @endif
                @endif 
            </form>
        </tr>

        @if($user->roles[0]['name'] != 'SUPER_ADMIN')
        <tr>
            <td>Reset Password</td>
            <td>
                <label for="password">New Password</label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autofocus>
                <span class="invalid-feedback" role="alert">
                    <strong></strong>
                </span>

                <label for="password-confirm">Confirm New Password</label>
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autofocus>
            </td>
            <td>
                <button id="reset_user_password">Reset Password</button>
            </td>
        </tr>
        @endif
    </table>
@endsection
