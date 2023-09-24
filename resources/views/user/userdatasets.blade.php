@extends('templates.layout')

@push('scripts')
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        var ajaxdeletedataset = "{{url('ajaxdeletedataset')}}";
        var ajaxjoindataset = "{{url('ajaxjoindataset')}}";
        var ajaxleavedataset = "{{url('ajaxleavedataset')}}";
    </script>
    <script src="{{ asset('/js/dataset.js') }}"></script>
@endpush

@section('content')

    <h2>{{ Auth::user()->name }}'s Layers</h2>
    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a><br>
    <a href="{{url('myprofile/mydatasets/newdataset')}}" class="mb-3 btn btn-primary">Create Layer</a>
    <div id='join_controls' class="meddiv hideme">
        <label for="join_link_input">To join, enter the link of the dataset you received in an email:</label>
        <input class="form-control w3-white" type="text" id="join_link_input">
        <button class="mb-3 btn btn-primary" id='join_link_button'>Submit</button>
        <button class="mb-3 btn btn-secondary" id='hide_join_controls_button'>Cancel</button>
    </div>
    <div id="success" class="w3-panel w3-pale-green w3-border hideme">Success!</div>

    <table id="datasettable" class="display" style="width:100%">
        <thead class="w3-black"><tr><th>Name</th><th>Size</th><th>Dataset Role</th><th>Visibility</th><th>Created</th><th>Updated</th><th>Delete</th></tr></thead>
        <tbody>
        @foreach($user->datasets as $ds)
            <tr id="row_id_{{$ds->id}}">
                <td><a href="{{url()->full()}}/{{$ds->id}}">{{$ds->name}}</a></td>
                <td>{{count($ds->dataitems)}}</td>
                <td>{{$ds->pivot->dsrole}}</td>
                <td>@if($ds->public)Public @else Private @endif</td>
                <td>{{$ds->created_at}}</td>
                <td>{{$ds->updated_at}}</td>
                <td>
                    @if($user->id == $ds->owner())
                    <!-- Delete dataset button -->
                    <button name="delete_dataset_button" id="delete_dataset_button_{{$ds->id}}">Delete</button></td>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a>
    <div class="notification" id="notification_box"><span id="notification_message" class="align-middle notification_message"></span></div>
@endsection
