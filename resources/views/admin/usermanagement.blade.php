@extends('templates.layout')

@push('scripts')
    <script src="{{ asset('js/usermanagement.js') }}"></script>
@endpush

@section('content')
    <h2>User Management</h2>
    <a href="{{ url('/admin') }}" class="mb-3 btn btn-primary">Back</a><br>
    <table id="usertable" class="display" style="width:100%">
        <thead class="w3-black"><tr><th>ID</th><th>Email</th><th>Name</th><th>Role</th><th>Active</th><th>Created</th><th>Updated</th></tr></thead>
        <tbody>
        @foreach($users as $user)
        <tr>
            <td><a href="{{ url('/admin/users') }}/{{ $user->id }}">{{ $user->id }}</a></td>
            <td><a href="{{ url('/admin/users') }}/{{ $user->id }}">{{ $user->email }}</a></td>
            <td>{{ $user->name }}</td>
            <td>
                @for($i = 0; $i < count($user->roles); $i++)
                @if($i > 0)
                    ,
                @endif
                {{$user->roles[$i]['name']}}
                @endfor
            </td>
            @if($user->is_active)
            <td class="text-success">Active</td>
            @else
            <td class="text-danger">Inactive</td>
            @endif
            <td>{{ $user->created_at }}</td>
            <td>{{ $user->updated_at }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
@endsection
