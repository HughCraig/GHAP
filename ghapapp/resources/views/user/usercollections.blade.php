@extends('templates.layout')

@section('content')
    <script>
        const deleteCollectionService = "{{url('ajaxdeletecollection')}}";

        $(document).ready(function () {
            $("#collectionsTable").dataTable({
                orderClasses: false,
                bPaginate: true,
                bFilter: true,
                bInfo: false,
                bSortable: true,
                bRetrieve: true,
                aaSorting: [[0, "asc"]],
                aoColumnDefs: [
                    {"aTargets": [6], "bSortable": false, "bSearchable": false},
                ],
                "pageLength": 25
            });
        });
    </script>

    <h2>{{ $user->name }}'s Multilayers</h2>
    
    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a><br>
    <a href="{{url('myprofile/mycollections/newcollection')}}" class="mb-3 btn btn-primary">Create Multilayer</a>
    
    <table id="collectionsTable" class="display" style="width:100%">
        <thead class="w3-black">
        <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Contributor</th>
            <th>Visibility</th>
            <th>Created</th>
            <th>Updated</th>
            <th>Delete</th>
        </tr>
        </thead>
        <tbody>
        @foreach($collections as $collection)
            <tr id="row_id_{{$collection->id}}">
                <td><a href="{{url()->full()}}/{{$collection->id}}">{{$collection->name}}</a></td>
                <td>{{count($collection->datasets)}}</td>
                <td>{{$collection->ownerUser->name}} (You)</td>
                <td>
                    @if($collection->public)
                        Public
                    @else
                        Private
                    @endif
                </td>
                <td>{{$collection->created_at}}</td>
                <td>{{$collection->updated_at}}</td>
                <td>
                    <button name="delete_collection_button" id="delete_collection_button_{{$collection->id}}">Delete</button></td>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <script src="{{ asset('/js/collection.js') }}"></script>
    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a>
    <div class="notification" id="notification_box">
        <span id="notification_message" class="align-middle notification_message"></span>
    </div>
@endsection
