@extends('templates.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/map-picker.css') }}">
@endpush

@push('scripts')
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        var ajaxviewdataitem = "{{url('ajaxviewdataitem')}}";
        var ajaxadddataitem = "{{url('ajaxadddataitem')}}";
        var ajaxeditdataitem = "{{url('ajaxeditdataitem')}}";
        var ajaxdeletedataitem = "{{url('ajaxdeletedataitem')}}";
        var ajaxchangedataitemorder = "{{url('ajaxchangedataitemorder')}}";
        var ajaxmarklayerasfeatured = "{{url('ajaxmarklayerasfeatured')}}";

        var lgas = {!! $lgas !!};
        var feature_terms = {!! $feature_terms !!};
        var dataset_id = {!! $ds->id !!};
    </script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="{{ asset('js/map-picker.js') }}"></script>
    <script src="{{ asset('js/message-banner.js') }}"></script>
    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/userviewdataset.js') }}"></script>
    <script src="{{ asset('js/extended-data-editor.js') }}"></script>
    <script src="{{ asset('/js/dataitem.js') }}"></script>
@endpush

@section('content')

    <h2>View Layer</h2>
    
    
<div class="d-flex flex-column flex-md-row gap-2">

    

    @if (!empty(config('app.views_root_url')) && $ds->public)
        <!-- Visualise-->
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="visualiseDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                View Map
            </button>
            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load=' + encodeURIComponent('{{url('')}}/layers/{{$ds->id}}/json'))">3D Viewer</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load=' + encodeURIComponent('{{url('')}}/layers/{{$ds->id}}/json'))">Cluster</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{url('')}}/layers/{{$ds->id}}/json?line=route'))">Journey Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{url('')}}/layers/{{$ds->id}}/json?line=time'))">Journey Times</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/timeline.html?load=' + encodeURIComponent('{{url('')}}/layers/{{$ds->id}}/json?sort=start'))">Timeline</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{url('')}}/layers/{{$ds->id}}/json'))">Werekata Flight by Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{url('')}}/layers/{{$ds->id}}/json?sort=start'))">Werekata Flight by Time</a>
                @if (!empty(config('app.views_temporal_earth_url')))
                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file={{url('')}}/kml')">Temporal Earth</a>
                @endif

                @if ($ds->recordtype->type == 'Text' && $ds->text)
                    <a class="dropdown-item grab-hover" 
                    onclick="window.open('{{ url()->current() }}/textmap?load=' + encodeURIComponent('{{ url('') }}/layers/{{$ds->id}}/json?textmap=true'))">
                        Text Map
                    </a>
                @endif
            </div>
        </div>

        
    @endif



    <!-- Basic Statistics Feed -->
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="analyseDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Analyse
        </button>
        <div class="dropdown-menu" aria-labelledby="analyseDropdown">
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/basicstatistics">Basic Statistics</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/advancedstatistics">Advanced Statistics</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/clusteranalysis">Cluster Analysis</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/temporalclustering">Temporal Clustering</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/closenessanalysis">Closeness Analysis</a>
        </div>
    </div>



<!-- Export/Download -->
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Download
        </button>
        <div class="dropdown-menu" aria-labelledby="downloadDropdown">
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/kml/download">KML</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/csv/download">CSV</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/json/download">GeoJSON</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/ro-crate">RO-Crate</a>
        </div>
    </div>

    <!-- Web Services Feed -->
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="wsfeedDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            WS Feed
        </button>
        <div class="dropdown-menu" aria-labelledby="wsfeedDropdown">
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/kml">KML</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/csv">CSV</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/json">GeoJSON</a>
        </div>
    </div>

    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="Access, view and analyse this map layer in various ways.  
                        Use 'View Map' to see the layer on different kinds of map. Some map views may not be relevant to this dataset. 3D Viewer is the simplest.
                        Use 'Analyse' to see some statistics about this map or use 'clustering' to visualise distinct areas of intensity, or 'closeness' to compare two datasets.
                        Use 'Download' or 'WS Feed' to use the data offline or in other systems.">
</span>

</div>
    
    <!-- Quick Info -->
    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th class="w-25">Name</th><td>{{$ds->name}}</td></tr>
		            <tr style="height: 50px; overflow: auto"><th>Description</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->description) !!}</td></tr>
                    <tr style="height: 50px; overflow: auto"><th>Type</th><td>{{$ds->recordtype->type}}</td></tr>
                    <tr><th>Subject</th>
                        <td>
                        @for($i = 0; $i < count($ds->subjectKeywords); $i++)
                            @if($i == count($ds->subjectKeywords)-1)
                            {{$ds->subjectKeywords[$i]->keyword}}
                            @else
                            {{$ds->subjectKeywords[$i]->keyword}},
                            @endif
                        @endfor
                        </td>
                    </tr>
                    <tr><th>Visibility</th><td id="dspublic">@if($ds->public)Public @else Private @endif</td></tr>
                    <tr><th>Linkback</th><td id="linkback">{{$ds->linkback}}</td></tr>
                    <tr>
                        <th>Image</th>
                        <td>
                            @if($ds->image_path)
                            <img src="{{ asset('storage/images/' . $ds->image_path) }}" alt="Layer Image" style="max-width: 100%; max-height:150px">
                            @endif
                        </td>
                    </tr>
                    <tr><th>Content Warning</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->warning) !!}</td></tr>
                    
                    <tr><th>Your Role</th><td>{{$ds->pivot->dsrole}}</td></tr>
                    <tr><th>Contributor</th><td>{{$ds->ownerName()}} @if($ds->owner() == $user->id) (You) @endif</td></tr>
                    <tr><th>Entries</th><td id="dscount">{{count($ds->dataitems)}}</td></tr>
                    <tr><th>Added to System</th><td>{{$ds->created_at}}</td></tr>
                    <tr><th>Updated in System</th><td id="dsupdatedat">{{$ds->updated_at}}</td></tr>
                </table>
            </div>
        </div>

    <div class="col-lg-8 collapse d-lg-flex" id="extraInfo">
        <div class="col-lg-4">
            <div class="table-responsive" style="overflow: unset">
                <table class="table table-bordered">
                
                    <tr><th class="w-25">Creator</th><td>{{$ds->creator}}</td></tr>
                    <tr><th>Publisher</th><td>{{$ds->publisher}}</td></tr>
                    <tr><th>Contact</th><td>{{$ds->contact}}</td></tr>
                    <tr><th>DOI</th><td id="doi">{{$ds->doi}}</td></tr>
                    <tr><th>Source URL</th><td id="source_url">{{$ds->source_url}}</td></tr>
                    <tr><th>License</th><td>{{$ds->license}}</td></tr>
                    <tr><th>Allow ANPS Collection?</th><td id="dspublic">@if($ds->allowanps)Yes @else No @endif</td></tr>
                    <tr><th>Citation</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->citation) !!}</td></tr>
                    
                    <tr><th>Usage Rights</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->rights) !!}</td></tr>
                    
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                
                    <tr><th class="w-25">Language</th><td>{{$ds->language}}</td></tr>
                    <tr><th>Latitude From</th><td>{{$ds->latitude_from}}</td></tr>
                    <tr><th>Longitude From</th><td>{{$ds->longitude_from}}</td></tr>
                    <tr><th>Latitude To</th><td>{{$ds->latitude_to}}</td></tr>
                    <tr><th>Longitude To</th><td>{{$ds->longitude_to}}</td></tr>
                    <tr><th>Date From</th><td>{{$ds->temporal_from}}</td></tr>
                    <tr><th>Date To</th><td>{{$ds->temporal_to}}</td></tr>
                    <tr><th>Date Created (externally)</th><td>{{$ds->created}}</td></tr>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Toggle button visible only on small screens -->
    <div class="d-lg-none mt-2">
    <button class="btn btn-outline-secondary w-100"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#extraInfo"
            aria-expanded="false"
            aria-controls="extraInfo">
        Layer details
    </button>
    </div>

<div class="d-flex flex-column flex-md-row align-items-start gap-2 mt-3 mb-3">

    @if($ds->pivot->dsrole == 'OWNER' || $ds->pivot->dsrole == 'ADMIN' || $ds->pivot->dsrole == 'COLLABORATOR') 
    
            <!-- Edit Collaborators Button-->
        <!-- <a href="{{url()->full()}}/collaborators" class="btn btn-primary">Edit Collaborators</a> -->

        <!-- Edit Dataset Modal Button-->
        @include('modals.editdatasetmodal')

        @if ($ds->recordtype->type == 'Text' && $ds->text)
            <button class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false" onclick="window.open('{{ url()->current() }}/textmap?load=' + encodeURIComponent('{{ url('') }}/layers/{{$ds->id}}/json?textmap=true'))">
                Edit Text Map
            </button>
        @endif

        <!-- Modal Add to dataset button -->
        @include('modals.addtodatasetmodal')

        <!-- MODAL Bulk Add Dataset button -->
        @include('modals.bulkaddtodatasetmodal')

<button id="toggle-drag" class="btn btn-primary">Change Order</button>


        <!-- Modal edit dataitem modal -->
        @include('modals.editdataitemmodal')

        <!-- Modal delete dataitem modal -->
        @include('modals.deleteconfirmmodal')
    
    @else
        @push('styles')
            <link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
            <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
        @endpush

        @push('scripts')
            <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
        @endpush

    @endif

@admin
        @if (isset($ds->featured_url))
            <button class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false" id="mark_layer_as_unfeatured">
                Remove featured
            </button>
        @else
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="markAsFeaturedLayerDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Mark as featured
                </button>
                <div class="dropdown-menu" aria-labelledby="markAsFeaturedLayerDropdown">
                    <a class="dropdown-item grab-hover mark_layer_as_featured" data-featured-url="{{ config('app.views_root_url') }}/3d.html?load={{ urlencode(url('/layers/' . $ds->id) . '/json') }}">3D Viewer</a>
                    <a class="dropdown-item grab-hover mark_layer_as_featured" data-featured-url="{{ config('app.views_root_url') }}/cluster.html?load={{ urlencode(url('/layers/' . $ds->id) . '/json') }}">Cluster</a>
                    <a class="dropdown-item grab-hover mark_layer_as_featured" data-featured-url="{{ config('app.views_root_url') }}/journey.html?load={{ urlencode(url('/layers/' . $ds->id) . '/json?line=route') }}">Journey Route</a>
                    <a class="dropdown-item grab-hover mark_layer_as_featured" data-featured-url="{{ config('app.views_root_url') }}/journey.html?load={{ urlencode(url('/layers/' . $ds->id) . '/json?line=time') }}">Journey Times</a>
                    <a class="dropdown-item grab-hover mark_layer_as_featured" data-featured-url="{{ config('app.views_root_url') }}/timeline.html?load={{ urlencode(url('/layers/' . $ds->id) . '/json?sort=start') }}">Timeline</a>
                    <a class="dropdown-item grab-hover mark_layer_as_featured" data-featured-url="{{ config('app.views_root_url') }}/werekata.html?load={{ urlencode(url('/layers/' . $ds->id) . '/json') }}">Werekata Flight by Route</a>
                    <a class="dropdown-item grab-hover mark_layer_as_featured" data-featured-url="{{ config('app.views_root_url') }}/werekata.html?load={{ urlencode(url('/layers/' . $ds->id) . '/json?sort=start') }}">Werekata Flight by Time</a>
                    @if ($ds->recordtype->type == 'Text' && $ds->text)
                        <a class="dropdown-item grab-hover mark_layer_as_featured" data-featured-url="{{ config('app.views_root_url') }}/fulltext.html?load={{ urlencode(url('/layers/' . $ds->id) . '/json?textmap=true') }}">Full Text</a>
                    @endif
                </div>

            </div>
        @endif
    @endadmin
<span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="Use 'Add place to layer' to add points one by one, or 'Import source' to upload a CSV, KML or GeoJSON file of spatial data. See the Help guide for details on file format.
                        Use 'Change Order' to change the sequence of points if viewing on a Journey by Route map.">
</span>
</div>

<h3>Places</h3>

    <!-- Dataitem Table -->
    <div class="container-fluid">
        <div class="place-list">
            @foreach($ds->dataitems as $data)
                <div class="row gy-2 gy-xl-0 mb-3" data-id="{{ $data->id }}">
                    <div class="col dragIcon" style="max-width: 4%;display:none">
                        <img src="{{ asset('img/draggable.svg') }}">
                    </div>
                    <div class="col-12 col-xl-2">
                        <h4>
                            @if ($ds->public)
                                <button type="button" class="btn btn-primary btn-sm" onclick="copyLink('{{ $data->uid }}',this,'id')">C</button>
                                <a href="{{config('app.url')}}/places/{{ \TLCMap\Http\Helpers\UID::create($data->id, 't') }}">
                            @endif
                            @if(isset($data->title)){{$data->title}}@else{{$data->placename}}@endif
                            @if ($ds->public)
                                </a>
                            @endif
                        </h4>
                        <dl>
                            @if(isset($data->placename))<dt>Placename</dt><dd>{{$data->placename}}</dd>@endif
                            @if(isset($data->recordtype_id))<dt>Type</dt><dd>{{$data->recordtype->type}}</dd>
                            @elseif(isset($data->dataset->recordtype_id))<dt>Type</dt><dd>{{$data->dataset->recordtype->type}}</dd>
                            @endif
                            <div class="dropdown mb-3">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    🌏 View Maps...
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    @if (!empty(config('app.views_root_url')) && $ds->public)
                                        <a class="dropdown-item grab-hover"
                                           onclick="window.open(`{{ config('app.views_root_url') }}/3d.html?load={{ urlencode(config('app.url'). '/places/' . \TLCMap\Http\Helpers\UID::create($data->id, 't') . '/json') }}`)">3D Viewer</a>
                                    @endif
                                    <a class="dropdown-item grab-hover" onclick="window.open('https\:\/\/www.google.com/maps/search/?api=1&query={{$data->latitude}},{{$data->longitude}}')">Google Maps</a>
                                    @if(isset($data->placename)) <a class="dropdown-item grab-hover" target="_blank" href="https://trove.nla.gov.au/search?keyword={{$data->placename}}">Trove Search</a>
                                    @else<a class="dropdown-item grab-hover" target="_blank" href="https://trove.nla.gov.au/search?keyword={{$data->title}}">Trove Search</a>@endif

                                </div>
                            </div>
                            <div class="mb-3">
                                <button type="button" data-item-id="{{ $data->id }}" data-set-id="{{ $ds->id }}" class="btn btn-primary edit-dataitem-button">Edit</button>
                                <button type="button" data-item-id="{{ $data->id }}" data-set-id="{{ $ds->id }}" class="btn btn-default delete-dataitem-button">Delete</button>
                            </div>
                        </dl>
                    </div>
                    <div class="col-12 col-xl-2">

                        <button class="btn btn-outline-secondary w-100 text-start fw-semibold py-2 d-xl-none collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#details-805453"
                                aria-expanded="false"
                                aria-controls="details-805453">
                            Location, Dates
                        </button>

                        <!-- Desktop heading (only shows on xl+) -->
                        <h4 class="d-none d-xl-block">Location, Dates</h4>

                        <!-- Body: collapsed on small, always shown on xl+ -->
                        <div id="details-805453" class="collapse d-xl-block">

                        @if(isset($data->latitude))<dt>Latitude</dt><dd>{{$data->latitude}}</dd>@endif
                        @if(isset($data->longitude))<dt>Longitude</dt><dd>{{$data->longitude}}</dd>@endif
                        @if(isset($data->datestart))<dt>Start Date</dt><dd>{{$data->datestart}}</dd>@endif
                        @if(isset($data->dateend))<dt>End Date</dt><dd>{{$data->dateend}}</dd>@endif

                        @if(isset($data->state))<dt>State</dt><dd>{{$data->state}}</dd>@endif
                        @if(isset($data->lga))<dt>LGA</dt><dd>{{$data->lga}}</dd>@endif
                        @if(isset($data->parish))<dt>Parish</dt><dd>{{$data->parish}}</dd>@endif
                        @if(isset($data->feature_term))<dt>Feature Term</dt><dd>{{$data->feature_term}}</dd>@endif

                        </div>
                    </div>

                    <div class="col-12 col-xl-2">
                        <button class="btn btn-outline-secondary w-100 text-start fw-semibold py-2 d-xl-none collapsed"
                                data-bs-toggle="collapse" data-bs-target="#desc-805453">
                            Description
                        </button>
                        <h4 class="d-none d-xl-block">Description</h4>
                        <div id="desc-805453" class="collapse d-xl-block">
                        @if(isset($data->description))
                            <div>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($data->description) !!}</div>
                        @endif
                        @if(isset($data->extended_data))
                        </div>
                    </div>
                    <div class="col-12 col-xl-2">
                        <button class="btn btn-outline-secondary w-100 text-start fw-semibold py-2 d-xl-none collapsed"
                                data-bs-toggle="collapse" data-bs-target="#extdata-805453">
                            Extended Data
                        </button>
                        <h4 class="d-none d-xl-block">Extended Data</h4>
                        <div id="extdata-805453" class="collapse d-xl-block">
                        {!!$data->extDataAsHTML()!!}
                        @endif
                        </div>
                    </div>
                    <div class="col-12 col-xl-2">
                        <button class="btn btn-outline-secondary w-100 text-start fw-semibold py-2 d-xl-none collapsed"
                                data-bs-toggle="collapse" data-bs-target="#sources-805453">
                            Sources
                        </button>
                        <h4 class="d-none d-xl-block">Sources</h4>
                        <div id="sources-805453" class="collapse d-xl-block">
                        @if(isset($data->glycerine_url))<dd><a href="{{$data->glycerine_url}}" target="_blank">Open Glycerine Image</a></dd>@endif
                        @if(isset($data->uid))<dt>TLCMap ID</dt><dd>{{$data->uid}}</dd>@endif
                        @if(isset($data->external_url))<dt>Linkback</dt><dd><a href="{{$data->external_url}}">{{$data->external_url}}</a></dd>@endif
                        @if(isset($data->source))<dt>Source</dt><dd>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($data->source) !!}</dd>@endif

                        @if(isset($data->created_at))<dt>Created At</dt><dd>{{$data->created_at}}</dd>@endif
                        @if(isset($data->updated_at))<dt id="updatedat">Updated At</dt><dd>{{$data->updated_at}}</dd>@endif
                        </div>
                    </div>
                    @if(!empty($data->image_path))
                        <div class="col-12 col-xl-2">
                            <img src="{{ asset('storage/images/' . $data->image_path) }}" alt="Place image" style="max-width: 100%;max-height:150px">
                        </div>
                    @endif
                    <!-- end bootstrap row -->
                </div>
            @endforeach
        </div>
        <!-- end bootstrap container -->
    </div>

@endsection
