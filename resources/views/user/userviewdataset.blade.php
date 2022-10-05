@extends('templates.layout')

@push('scripts')
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        var ajaxadddataitem = "{{url('ajaxadddataitem')}}";
        var ajaxeditdataitem = "{{url('ajaxeditdataitem')}}";
        var ajaxdeletedataitem = "{{url('ajaxdeletedataitem')}}";

        var lgas = {!! $lgas !!};
        var feature_terms = {!! $feature_terms !!};
    </script>
    <script src="{{ asset('js/userviewdataset.js') }}"></script>
    <!-- for description fields -->
    <script src="/ghap/js/tinymce/tinymce.min.js"></script>
    <script src="/ghap/js/wysiwyger.js"></script>
    <script src="{{ asset('/js/dataitem.js') }}"></script>
@endpush

@section('content')

    <h2>View Layer</h2>
    <a href="{{url('myprofile/mydatasets')}}" class="btn btn-primary">Back</a>
    
    @if($ds->pivot->dsrole == 'ADMIN' || $ds->pivot->dsrole == 'OWNER') 

        <!-- Edit Collaborators Button-->
        <a href="{{url()->full()}}/collaborators" class="btn btn-primary">Edit Collaborators</a>

        <!-- Edit Dataset Modal Button-->
        @include('modals.editdatasetmodal')
    @else
        @push('styles')
            <link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
            <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
        @endpush

        @push('scripts')
            <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
        @endpush
    @endif

    <!-- Export/Download -->
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="downloadDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Download
        </button>
        <div class="dropdown-menu" aria-labelledby="downloadDropdown">
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/kml/download">KML</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/csv/download">CSV</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/json/download">GeoJSON</a>
        </div>
    </div>

    <!-- Web Services Feed -->
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="wsfeedDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            WS Feed
        </button>
        <div class="dropdown-menu" aria-labelledby="wsfeedDropdown">
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/kml">KML</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/csv">CSV</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/json">GeoJSON</a>
        </div>
    </div>

    @if (!empty(config('app.views_root_url')))
        <!-- Visualise-->
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                View Map
            </button>
            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load={{url('')}}/publicdatasets/{{$ds->id}}/json')">3D Viewer</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load={{url('')}}/publicdatasets/{{$ds->id}}/json')">Cluster</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?line=route&load={{url('')}}/publicdatasets/{{$ds->id}}/json')">Journey Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?line=time&load={{url('')}}/publicdatasets/{{$ds->id}}/json')">Journey Times</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/timeline.html?load={{url('')}}/publicdatasets/{{$ds->id}}/json?sort=start')">Timeline</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?&load={{url('')}}/publicdatasets/{{$ds->id}}/json')">Werekata Flight by Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?sort=start&load={{url('')}}/publicdatasets/{{$ds->id}}/json')">Werekata Flight by Time</a>
                @if (!empty(config('app.views_temporal_earth_url')))
                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file={{url('')}}/kml')">Temporal Earth</a>
                @endif
            </div>
        </div>
    @endif
    
    <!-- Quick Info -->
    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th class="w-25">Name</th><td>{{$ds->name}}</td></tr>
		            <tr style="height: 50px; overflow: auto"><th>Description</th><td>{{$ds->description}}</td></tr>
                    <tr style="height: 50px; overflow: auto"><th>Type</th><td>{{$ds->recordtype->type}}</td></tr>
                    <tr><th>Content Warning</th><td>{{$ds->warning}}</td></tr>
                    <tr><th>Your Role</th><td>{{$ds->pivot->dsrole}}</td></tr>
                    <tr><th>Contributor</th><td>{{$ds->ownerName()}} @if($ds->owner() == $user->id) (You) @endif</td></tr>
                    <tr><th>Entries</th><td id="dscount">{{count($ds->dataitems)}}</td></tr>
                    <tr><th>Visibility</th><td id="dspublic">@if($ds->public)Public @else Private @endif</td></tr>
                    <tr><th>Allow ANPS Collection?</th><td id="dspublic">@if($ds->allowanps)Yes @else No @endif</td></tr>
                    <tr><th>Added to System</th><td>{{$ds->created_at}}</td></tr>
                    <tr><th>Updated in System</th><td id="dsupdatedat">{{$ds->updated_at}}</td></tr>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive" style="overflow: unset">
                <table class="table table-bordered">
                <tr><th class="w-25">Subject</th>
                    <td>
                        @for($i = 0; $i < count($ds->subjectkeywords); $i++)
                            @if($i == count($ds->subjectkeywords)-1)
                            {{$ds->subjectkeywords[$i]->keyword}}
                            @else
                            {{$ds->subjectkeywords[$i]->keyword}},
                            @endif
                        @endfor
                    </td>
                </tr>
                    <tr><th>Creator</th><td>{{$ds->creator}}</td></tr>
                    <tr><th>Publisher</th><td>{{$ds->publisher}}</td></tr>
                    <tr><th>Contact</th><td>{{$ds->contact}}</td></tr>
                    <tr><th>Citation</th><td>{{$ds->citation}}</td></tr>
                    <tr><th>DOI</th><td id="doi">{{$ds->doi}}</td></tr>
                    <tr><th>Source URL</th><td id="source_url">{{$ds->source_url}}</td></tr>
                    <tr><th>Linkback</th><td id="linkback">{{$ds->linkback}}</td></tr>
                    <tr><th>Date From</th><td>{{$ds->temporal_from}}</td></tr>
                    <tr><th>Date To</th><td>{{$ds->temporal_to}}</td></tr>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th class="w-25">Latitude From</th><td>{{$ds->latitude_from}}</td></tr>
                    <tr><th>Longitude From</th><td>{{$ds->longitude_from}}</td></tr>
                    <tr><th>Latitude To</th><td>{{$ds->latitude_to}}</td></tr>
                    <tr><th>Longitude To</th><td>{{$ds->longitude_to}}</td></tr>
                    <tr><th>Language</th><td>{{$ds->language}}</td></tr>
                    <tr><th>License</th><td>{{$ds->license}}</td></tr>
                    <tr><th>Rights</th><td>{{$ds->rights}}</td></tr>
                    <tr><th>Date Created (externally)</th><td>{{$ds->created}}</td></tr>
                </table>
            </div>
        </div>
    </div>

    @if($ds->pivot->dsrole == 'OWNER' || $ds->pivot->dsrole == 'ADMIN' || $ds->pivot->dsrole == 'COLLABORATOR') 
    
        <!-- Modal Add to dataset button -->
        @include('modals.addtodatasetmodal')

        <!-- MODAL Bulk Add Dataset button -->
        @include('modals.bulkaddtodatasetmodal')
    @endif
    
    <!-- Dataitem Table -->
    <table id="dataitemtable" class="display" style="width:100%">
        <thead class="w3-black"><tr>
            <th>Title</th><th>Placename</th><th>Type</th><th>Description</th><th>Latitude</th><th>Longitude</th><th>Date Start</th><th>Date End</th><th>State</th><th>Feature Term</th><th>LGA</th><th>Source</th><th>Linkback</th><th>Visualise</th><th>Created</th><th>Updated</th><th>Edit</th><th>Delete</th>
        </tr></thead>
        <tbody>
        @foreach($ds->dataitems as $data)
            <tr id="row_id_{{$data->id}}">
                <td data-order="{{$data->title}}" data-search="{{$data->title}}">
                    <input class="inputastd" type="text" id="title" name="title" disabled="true" value="{{$data->title}}" oldvalue="{{$data->title}}"></td>
		        <td data-order="{{$data->placename}}" data-search="{{$data->placename}}">
                    <input class="inputastd" type="text" id="placename" name="placename" disabled="true" value="{{$data->placename}}" oldvalue="{{$data->placename}}"></td> 
                <td data-order="{{$data->recordtype->type}}" data-search="{{$data->recordtype->type}}">
                    <select class="inputastd" type="text" id="recordtype" name="recordtype" disabled="true" value="{{$data->recordtype->type}}" oldvalue="{{$data->recordtype->type}}">
                        @foreach($recordtypes as $type)
                            @if($type == $data->recordtype->type) <option label="{{$type}}" selected>{{$type}}</option>
                            @else <option label="{{$type}}">{{$type}}</option> @endif
                        @endforeach
                    </select></td>
                
                <td data-order="{{$data->description}}" data-search="{{$data->description}}">
                    <!-- script for wysiwyg with tinymce is referenced in userviewdataset.blade.php. Add 'wysiwyger' class to apply it. See wysiwyger.js. Removed for now till we can work on it properly. -->
                    <input class="inputastd" type="text" id="description" name="description" disabled="true" value="{{$data->description}}" oldvalue="{{$data->description}}"></td>
                <td data-order="{{$data->latitude}}" data-search="{{$data->latitude}}">
                    <input class="inputastd" type="text" id="latitude" name="latitude" disabled="true" value="{{$data->latitude}}" oldvalue="{{$data->latitude}}"></td>
                <td data-order="{{$data->longitude}}" data-search="{{$data->longitude}}">
                    <input class="inputastd" type="text" id="longitude" name="longitude" disabled="true" value="{{$data->longitude}}" oldvalue="{{$data->longitude}}"></td>
<!-- 
                <td data-order="{{$data->datestart}}" data-search="{{$data->datestart}}">
                    <input class="inputastd" type="text" id="datestart" name="datestart" disabled="true" value="{{$data->datestart}}" oldvalue="{{$data->datestart}}"></td>   
                <td data-order="{{$data->dateend}}" data-search="{{$data->dateend}}">
                    <input class="inputastd" type="text" id="dateend" name="dateend" disabled="true" value="{{$data->dateend}}" oldvalue="{{$data->dateend}}"></td> -->

                <td data-order="{{$data->datestart}}" data-search="{{$data->datestart}}">
                    <div class="input-group date" name="editdatestartdiv">
                        <input type="text" class="inputastd input-group-addon" id="datestart" name="datestart" disabled="true" value="{{$data->datestart}}" oldvalue="{{$data->datestart}}" autocomplete="off"/></div></td>

                <td data-order="{{$data->dateend}}" data-search="{{$data->dateend}}">
                    <div class="input-group date" name="editdateenddiv">
                        <input type="text" class="inputastd input-group-addon" id="dateend" name="dateend" disabled="true" value="{{$data->dateend}}" oldvalue="{{$data->dateend}}" autocomplete="off"/></div></td>

                <td data-order="{{$data->state}}" data-search="{{$data->state}}">
                    <select class="inputastd" type="text" id="state" name="state" disabled="true" value="{{$data->state}}" oldvalue="{{$data->state}}">
                        @foreach($states as $state)
                            @if($state->state_code == $data->state) <option label="{{$state->state_code}}" selected>{{$state->state_code}}</option> 
                            @else <option label="{{$state->state_code}}">{{$state->state_code}}</option> @endif
                        @endforeach
                    </select></td>
                <td data-order="{{$data->feature_term}}" data-search="{{$data->feature_term}}">
                    <input class="inputastd" type="text" id="feature_term" name="feature_term" disabled="true" value="{{$data->feature_term}}" oldvalue="{{$data->feature_term}}"></td>
                <td data-order="{{$data->lga}}" data-search="{{$data->lga}}">
                    <input class="inputastd" type="text" id="lga" name="lga" disabled="true" value="{{$data->lga}}" oldvalue="{{$data->lga}}"></td>
                <td data-order="{{$data->source}}" data-search="{{$data->source}}">
                    <input class="inputastd" type="text" id="source" name="source" disabled="true" value="{{$data->source}}" oldvalue="{{$data->source}}"></td>
                <td data-order="{{$data->external_url}}" data-search="{{$data->external_url}}">
                    <input class="inputastd" type="text" id="external_url" name="external_url" disabled="true" value="{{$data->external_url}}" oldvalue="{{$data->external_url}}"></td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            View In...
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @if (!empty(config('app.views_root_url')))
                                <a class="dropdown-item grab-hover"
                                    onclick="window.open('{{ config('app.views_root_url') }}/place.html?latlng={!! urlencode($data->latitude) !!},{!! urlencode($data->longitude) !!}&id={!! urlencode($data->id) !!}&title={!! urlencode($data->title) !!}&placename={!! urlencode($data->placename) !!}&description={!! urlencode($data->description) !!}&linkback={{url()->full()}}')">3D Viewer</a>
                            @endif
                            <a class="dropdown-item grab-hover" onclick="window.open('https\:\/\/www.google.com/maps/search/?api=1&query={{$data->latitude}},{{$data->longitude}}')">Google Maps</a>

                        </div>
                    </div>
                </td>
                <td>{{$data->created_at}}</td>
                <td id="updatedat">{{$data->updated_at}}</td>
                
                <td>
                    @if($ds->pivot->dsrole == 'ADMIN' || $ds->pivot->dsrole == 'OWNER')
                    <!-- Edit Data Item Button -->
                    <button class="hideme" name="edit_dataitem_button" id="edit_dataitem_button_{{$data->id}}" type="Submit">Submit</button>
                    <button class="hideme" name="edit_dataitem_button_cancel" id="edit_dataitem_button_cancel_{{$data->id}}" type="Submit">Cancel</button>
                    <button name="edit_dataitem_button_show" id="edit_dataitem_button_show_{{$data->id}}" type="Submit">Edit</button>
                    @endif
                </td>
                <td>
                    @if($ds->pivot->dsrole == 'ADMIN' || $ds->pivot->dsrole == 'OWNER') 
                    <!-- Delete Data Item Button -->
                    <button name="delete_dataitem_button" id="delete_dataitem_button_{{$data->id}}" type="Submit">Delete</button>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <a href="{{url('myprofile/mydatasets')}}" class="mb-3 btn btn-primary">Back</a>
@endsection
