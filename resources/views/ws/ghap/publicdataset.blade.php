@extends('templates.layout')

@push('scripts')
    <script>
        var dataset_id = {!! $ds->id !!};
        var ajaxmarklayerasfeatured = "{{url('ajaxmarklayerasfeatured')}}";
    </script>
    <script src="{{ asset('js/message-banner.js') }}"></script>
    <script src="{{ asset('js/publicdataset.js') }}"></script>
    <script src="{{ asset('/js/dataitem.js') }}"></script>
@endpush

@section('content')

    <h2>Layer</h2>

<div class="d-flex flex-column flex-md-row gap-2">

   @if (!empty(config('app.views_root_url')))
        <!-- Visualise-->
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="visualiseDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            🌏 View Maps...
            </button>
            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load=' + encodeURIComponent('{{url()->full()}}/json'))">3D Viewer</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load=' + encodeURIComponent('{{url()->full()}}/json'))">Cluster</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{url()->full()}}/json?line=route'))">Journey Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{url()->full()}}/json?line=time'))">Journey Times</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/timeline.html?load=' + encodeURIComponent('{{url()->full()}}/json?sort=start'))">Timeline</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{url()->full()}}/json'))">Werekata Flight by Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{url()->full()}}/json?sort=start'))">Werekata Flight by Time</a>
                @if (!empty(config('app.views_temporal_earth_url')))
                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file={{url()->full()}}/kml')">Temporal Earth</a>
                @endif

                @if ($ds->recordtype->type == 'Text' && $ds->text)
                    <a class="dropdown-item grab-hover" 
                    onclick="window.open('{{ config('app.views_root_url') }}/fulltext.html?load=' + encodeURIComponent('{{url()->full()}}/json?textmap=true'))">
                        Full Text
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if ($ds->public)
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
    @endif

    <!-- Export/Download -->
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle tlcmgreen" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
        <button class="btn btn-secondary dropdown-toggle tlcmgreen" type="button" id="wsfeedDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            WS Feed
        </button>
        <div class="dropdown-menu" aria-labelledby="wsfeedDropdown">
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/kml">KML</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/csv">CSV</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/json">GeoJSON</a>
        </div>
    </div>

 

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

</div>

    <!-- Quick Info -->
    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th class="w-25">Name</th><td>{{$ds->name}}</td></tr>
		            <tr><th>Description</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->description) !!}</td></tr>
		            <tr><th>Type</th><td>{{$ds->recordtype->type}}</td></tr>
                    <tr><th class="w-25">Subject</th>
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
                    
                    <tr><th>Number of places</th><td id="dscount">{{count($ds->dataitems)}}</td></tr>
                </table>
            </div>
        </div>

<div class="col-lg-8 collapse d-lg-flex" id="extraInfo">
        <div class="col-lg-4">
            <div class="table-responsive" style="overflow: unset">
                <table class="table table-bordered">
                    <tr><th>Contributor</th><td>{{$ds->ownerName()}}</td></tr>
                    <tr><th>Creator</th><td>{{$ds->creator}}</td></tr>
                    <tr><th>Publisher</th><td>{{$ds->publisher}}</td></tr>
                    <tr><th>Contact</th><td>{{$ds->contact}}</td></tr>
                    <tr><th>DOI</th><td id="doi">{{$ds->doi}}</td></tr>
                    <tr><th>Source URL</th><td id="source_url">{{$ds->source_url}}</td></tr>
                    <tr><th>License</th><td>{{$ds->license}}</td></tr>
                    <tr><th>Allow ANPS?</th><td id="dspublic">@if($ds->allowanps)Yes @else No @endif</td></tr>
                    <tr><th>Citation</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->citation) !!}</td></tr>
                    <tr><th>Usage Rights</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->rights) !!}</td></tr>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th>Language</th><td>{{$ds->language}}</td></tr>
                    <tr><th class="w-25">Latitude From</th><td>{{$ds->latitude_from}}</td></tr>
                    <tr><th>Longitude From</th><td>{{$ds->longitude_from}}</td></tr>
                    <tr><th>Latitude To</th><td>{{$ds->latitude_to}}</td></tr>
                    <tr><th>Longitude To</th><td>{{$ds->longitude_to}}</td></tr>
                    <tr><th>Date From</th><td>{{$ds->temporal_from}}</td></tr>
                    <tr><th>Date To</th><td>{{$ds->temporal_to}}</td></tr> 
                    <tr><th>Date Created (externally)</th><td>{{$ds->created}}</td></tr>
                    <tr><th>Added</th><td>{{$ds->created_at}}</td></tr>
                    <tr><th>Updated</th><td id="dsupdatedat">{{$ds->updated_at}}</td></tr>
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

    <!-- Dataitem Table -->

    <div class="container-fluid">
        <div class="place-list">
            @foreach($ds->dataitems as $data)
                <div class="row gy-2 gy-xl-0 mb-3">
                    <div class="col-12 col-xl-2">
                        <h4><button type="button" class="btn btn-primary btn-sm" onclick="copyLink('{{ $data->uid }}',this,'id')">C</button>
                            <a href="{{config('app.url')}}/places/{{ \TLCMap\Http\Helpers\UID::create($data->id, 't') }}">
                                @if(isset($data->title)){{$data->title}}@else{{$data->placename}}@endif</a>
                        </h4>
                        <dl>
                            @if(isset($data->placename))<dt>Placename</dt><dd>{{$data->placename}}</dd>@endif
                            @if(isset($data->recordtype_id))<dt>Type</dt><dd>{{$data->recordtype->type}}</dd>
                            @elseif(isset($data->dataset->recordtype_id))<dt>Type</dt><dd>{{$data->dataset->recordtype->type}}</dd>
                            @endif
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    🌏 View Maps...
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    @if (!empty(config('app.views_root_url')))
                                        <a class="dropdown-item grab-hover"
                                           onclick="window.open(`{{ config('app.views_root_url') }}/3d.html?load={{ urlencode(config('app.url'). '/places/' . \TLCMap\Http\Helpers\UID::create($data->id, 't') . '/json') }}`)">3D Viewer</a>
                                    @endif
                                    <a class="dropdown-item grab-hover" onclick="window.open('https\:\/\/www.google.com/maps/search/?api=1&query={{$data->latitude}},{{$data->longitude}}')">Google Maps</a>
                                    @if(isset($data->placename)) <a class="dropdown-item grab-hover" target="_blank" href="https://trove.nla.gov.au/search?keyword={{$data->placename}}">Trove Search</a>
                                    @else<a class="dropdown-item grab-hover" target="_blank" href="https://trove.nla.gov.au/search?keyword={{$data->title}}">Trove Search</a>@endif

                                </div>
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


    <a href="{{ route('layers') }}" class="mb-3 btn btn-primary">All Layers</a>

@endsection
