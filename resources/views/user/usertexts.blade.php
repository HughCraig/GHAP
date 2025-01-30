@extends('templates.layout')

@push('scripts')
<script>
    const deletetexturl = "{{url('ajaxdeletetext')}}";
</script>
<script src="{{ asset('/js/texts.js') }}"></script>
@endpush

@section('content')

<h2>{{ $user->name }}'s Texts</h2>

<div class="d-flex">
    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a><br>
    <a href="{{url('myprofile/mytexts/newtext')}}" class="mb-3 ml-2 btn btn-primary">Add Text</a>
</div>

<table id="textsTable" class="display" style="width:100%">
    <thead class="w3-black">
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Content Warning</th>
            <th>Created</th>
            <th>Updated</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
        @foreach($user->texts as $text)
        <tr id="row_id_{{$text->id}}">
            <td><a href="{{url()->full()}}/{{$text->id}}">{{$text->name}}</a></td>
            <td>{{ $text->texttype->type ?? 'N/A' }}</td>
            <td>{{$text->warning}}</td>
            <td>{{$text->created_at}}</td>
            <td>{{$text->updated_at}}</td>
            <td>
                <button name="delete_text_button" id="delete_text_button_{{$text->id}}">Delete</button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a>
<div class="notification" id="notification_box">
    <span id="notification_message" class="align-middle notification_message"></span>
</div>
@endsection