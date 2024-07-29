@extends('templates.layout')

@push('scripts')
    <script src="{{ asset('/js/collablink.js') }}"></script>
@endpush

@section('content')
    <h2>Edit Collaborators</h2>
    <!-- Dataset info -->
    <div class="row">
        <div class="col-xs-3">
            <table class="table-sm table-bordered">
                <tr>
                    <td>Name</td>
                    <td>{{ $ds->name }}</td>
                </tr>
                <tr style="height: 50px; overflow: auto">
                    <td>Description</td>
                    <td>{{ $ds->description }}</td>
                </tr>
                <tr>
                    <td>Role</td>
                    <td id="dsrole">{{ $ds->pivot->dsrole }}</td>
                </tr>
                <tr>
                    <td>Entries</td>
                    <td id="dscount">{{ $ds->getDataitemsCount() }}</td>
                </tr>
                <tr>
                    <td>Created</td>
                    <td>{{ $ds->created_at }}</td>
                </tr>
                <tr>
                    <td>Updated</td>
                    <td id="dsupdatedat">{{ $ds->updated_at }}</td>
                </tr>
                <div class="hideme" id="dsid">{{ $ds->id }}</div>
            </table>
        </div>

        <!-- list current collab info, options to edit -->
        <!-- When adding collaborators give option of using previous collab or generate a share link -->
        <!-- Share link saves to DB and expires in a day -->
        <div class="col-xs-3">
            <!-- Destroy Share Links Modal Button-->
            @include('modals.destroysharelinksmodal')

            <!-- Add Collaborator Modal Button-->
            @include('modals.addcollaboratormodal')

            <div id="success" class="w3-panel w3-pale-green w3-border hideme">Success!</div>
        </div>
    </div>

    <!-- Collaborator Table -->
    <table id="collabtable" class="display" style="width:100%">
        <thead class="w3-black">
            <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Role</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($ds->users()->get() as $dsuser)
                <tr>
                    <td data-order="{{ $dsuser->email }}" data-search="{{ $dsuser->email }}" name="collaborator_email">
                        {{ $dsuser->email }}</td>
                    <td data-order="{{ $dsuser->name }}" data-search="{{ $dsuser->name }}">{{ $dsuser->name }}</td>
                    <td data-order="{{ $dsuser->datasets()->find($ds->id)->pivot->dsrole }}"
                        data-search="{{ $dsuser->datasets()->find($ds->id)->pivot->dsrole }}">
                        <input type="text" name="old_dsrole" class="inputastd"
                            value="{{ $dsuser->datasets()->find($ds->id)->pivot->dsrole }}" disabled>
                        <select name="edit_collaborator_dsrole_selector" class="hideme" disabled>
                            <option value="ADMIN">ADMIN</option>
                            <option value="COLLABORATOR">COLLABORATOR</option>
                            </option>
                            <option value="VIEWER">VIEWER</option>
                        </select>
                    </td>
                    <td>
                        @if ($dsuser->id != $user->id && $dsuser->id != $ds->owner())
                            <!-- Edit Collaborator button -->
                            <button name='edit_collaborator_button'>Edit</button>
                            <button name="edit_collaborator_submit_button" class="hideme">Submit</button>
                            <button name="edit_collaborator_cancel_button" class="hideme">Cancel</button>
                        @endif
                    </td>
                    <td>
                        @if ($dsuser->id != $user->id && $dsuser->id != $ds->owner())
                            <!-- Delete Collaborator button -->
                            <button name="delete_collaborator_button">Delete</button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <a href="{{ substr(url()->full(), 0, strrpos(url()->full(), '/')) }}"
        class="mb-3 btn btn-primary">Back</a><!-- cutting off the end of the url so we keep the dataset id -->
    <div class="notification" id="notification_box"><span id="notification_message"
            class="align-middle notification_message"></span></div>
@endsection
