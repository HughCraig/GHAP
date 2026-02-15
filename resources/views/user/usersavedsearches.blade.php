@extends('templates.layout')

@push('scripts')
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        var ajaxdeletesearch = "{{url('ajaxdeletesearch')}}";
        var searches = {!! $searches !!};
        var recordTypeMap = {!! $recordTypeMap !!}
        var recordtypes = {!! $recordtypes !!}
        var subjectKeywordMap = {!! json_encode($subjectKeywordMap) !!};
    </script>
    <script src="{{ asset('js/usersavedsearches.js') }}"></script>
    <script src="{{ asset('/js/deletesearch.js') }}"></script>
@endpush

@section('content')
    @include('modals.savesearchmetadatamodal')
    @include('modals.savesearcheditmetadatamodal')
    <h2>{{ Auth::user()->name }}'s Saved Searches</h2>
    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a><br>
    <a href="{{url('/')}}" class="mb-3 btn btn-primary">Create a new search</a>

    <table id="savedsearchestable" class="display responsive" style="width:100%">
        <thead class="w3-black"><tr><th>Name</th><th>Result Size</th><th>Search URL</th><th>Keywords</td><th>Date Saved</th><th>Edit</th><th>Delete</th></tr></thead>
        <tbody>
        @foreach($searches as $search)
            <td><a href="#" class="openMetaDataModal" data-id="{{$search->id}}">{{$search->name}}</a></td>
            <td>{{$search->count}}</td>
            <td class="wordwrap"><a href="{{$search->query}}"> {{$search->query}}</a></td>
            <td>
            @for($i = 0; $i < count($search->subjectKeywords); $i++)
                                @if($i == count($search->subjectKeywords)-1)
                                {{$search->subjectKeywords[$i]->keyword}}
                                @else
                                {{$search->subjectKeywords[$i]->keyword}},
                                @endif
                @endfor
                </td>
            <td>{{$search->updated_at}}</td>
            <input type="hidden" name="delete_id" id="delete_id" value="{{$search->id}}" />
            <td><button class="openEditMetaDataModal" data-id="{{$search->id}}">Edit</button></td>
            <td><button name="delete_search_button" id="delete_search_button_{{$search->id}}" type="Submit">Delete</button></td></tr>
        @endforeach
        </tbody>
    </table>
    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a> 
@endsection
