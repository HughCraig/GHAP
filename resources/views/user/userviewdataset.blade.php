@extends('templates.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/map-picker.css') }}">
@endpush

@push('scripts')
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        var ajaxviewdataitem = "{{ url('ajaxviewdataitem') }}";
        var ajaxadddataitem = "{{ url('ajaxadddataitem') }}";
        var ajaxeditdataitem = "{{ url('ajaxeditdataitem') }}";
        var ajaxdeletedataitem = "{{ url('ajaxdeletedataitem') }}";
        var ajaxchangedataitemorder = "{{ url('ajaxchangedataitemorder') }}";

        var lgas = {!! $lgas !!};
        var feature_terms = {!! $feature_terms !!};
        // const max_upload_image_size = {{ config('app.max_upload_image_size') }};
        var dataset_id = {!! $ds->id !!};
    </script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="{{ asset('js/map-picker.js') }}"></script>
    <script src="{{ asset('js/message-banner.js') }}"></script>
    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/setmobilityfields.js') }}"></script>
    <script src="{{ asset('js/userviewdataset.js') }}"></script>
    <script src="{{ asset('js/extended-data-editor.js') }}"></script>
    <script src="{{ asset('/js/dataitem.js') }}"></script>
@endpush

@section('content')

    <h2>View Layer</h2>
    <a href="{{ url('myprofile/mydatasets') }}" class="btn btn-primary">Back</a>

    @if ($ds->pivot->dsrole == 'ADMIN' || $ds->pivot->dsrole == 'OWNER')
        <!-- Edit Collaborators Button-->
        <a href="{{ url()->full() }}/collaborators" class="btn btn-primary">Edit Collaborators</a>

        <button id="toggle-drag" class="btn btn-primary">Change Order</button>

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
        <button class="btn btn-secondary dropdown-toggle" type="button" id="downloadDropdown" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
            Download
        </button>
        <div class="dropdown-menu" aria-labelledby="downloadDropdown">
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/kml/download">KML</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/csv/download">CSV</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/json/download">GeoJSON</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/ro-crate">RO-Crate</a>
        </div>
    </div>

    <!-- Web Services Feed -->
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="wsfeedDropdown" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
            WS Feed
        </button>
        <div class="dropdown-menu" aria-labelledby="wsfeedDropdown">
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/kml">KML</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/csv">CSV</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/json">GeoJSON</a>
        </div>
    </div>

    @if (!empty(config('app.views_root_url')) && $ds->public)
        <!-- Visualise-->
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="visualiseDropdown" data-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                View Map
            </button>
            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                <a class="dropdown-item grab-hover"
                    onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json'))">3D
                    Viewer</a>
                <a class="dropdown-item grab-hover"
                    onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json'))">Cluster</a>
                <a class="dropdown-item grab-hover"
                    onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json?line=route'))">Journey
                    Route</a>
                <a class="dropdown-item grab-hover"
                    onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json?line=time'))">Journey
                    Times</a>
                <a class="dropdown-item grab-hover"
                    onclick="window.open('{{ config('app.views_root_url') }}/timeline.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json?sort=start'))">Timeline</a>
                <a class="dropdown-item grab-hover"
                    onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json'))">Werekata
                    Flight by Route</a>
                <a class="dropdown-item grab-hover"
                    onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json?sort=start'))">Werekata
                    Flight by Time</a>
                @if ($hasmobinfo['default'])
                    <a class="dropdown-item grab-hover"
                        onclick="window.open('{{ config('app.views_root_url') }}/mobility.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json?mobility=route'))">Mobility</a>
                    @if ($hasmobinfo['hasrouteiddatestart'])
                        <a class="dropdown-item grab-hover"
                            onclick="window.open('{{ config('app.views_root_url') }}/mobility.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json?mobility=timestart'))">Mobility
                            by Time Start</a>
                    @endif
                    @if ($hasmobinfo['hasrouteiddateend'])
                        <a class="dropdown-item grab-hover"
                            onclick="window.open('{{ config('app.views_root_url') }}/mobility.html?load=' + encodeURIComponent('{{ url('') }}/layers/{{ $ds->id }}/json?mobility=timeend'))">Mobility
                            by Time End</a>
                    @endif
                @endif
                @if (!empty(config('app.views_temporal_earth_url')))
                    <a class="dropdown-item grab-hover"
                        onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file={{ url('') }}/kml')">Temporal
                        Earth</a>
                @endif
            </div>
        </div>
    @endif

    <!-- Basic Statistics Feed -->
    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="analyseDropdown" data-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
            Analyse
        </button>
        <div class="dropdown-menu" aria-labelledby="analyseDropdown">
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/basicstatistics">Basic Statistics</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/advancedstatistics">Advanced Statistics</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/clusteranalysis">Cluster Analysis</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/temporalclustering">Temporal Clustering</a>
            <a class="dropdown-item grab-hover" href="{{ url()->full() }}/closenessanalysis">Closeness Analysis</a>
        </div>
    </div>

    <!-- Quick Info -->
    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th class="w-25">Name</th>
                        <td>{{ $ds->name }}</td>
                    </tr>
                    <tr style="height: 50px; overflow: auto">
                        <th>Description</th>
                        <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->description) !!}</td>
                    </tr>
                    <tr style="height: 50px; overflow: auto">
                        <th>Type</th>
                        <td>{{ $ds->recordtype->type }}</td>
                    </tr>
                    <tr>
                        <th>Content Warning</th>
                        <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->warning) !!}</td>
                    </tr>
                    <tr>
                        <th>Your Role</th>
                        <td>{{ $ds->pivot->dsrole }}</td>
                    </tr>
                    <tr>
                        <th>Contributor</th>
                        <td>{{ $ds->ownerName() }} @if ($ds->owner() == $user->id)
                                (You)
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Entries</th>
                        <td id="dscount">{{ $ds->dataitems_count }}</td>
                    </tr>
                    @if ($ds->recordtype->type === 'Mobility')
                        <tr>
                            <th>Route Entries</th>
                            <td id="tjccount">{{ $ds->routes_count }}</td>
                        </tr>
                    @endif
                    <tr>
                        <th>Visibility</th>
                        <td id="dspublic">
                            @if ($ds->public)
                                Public
                            @else
                                Private
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Allow ANPS Collection?</th>
                        <td id="dspublic">
                            @if ($ds->allowanps)
                                Yes
                            @else
                                No
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Added to System</th>
                        <td>{{ $ds->created_at }}</td>
                    </tr>
                    <tr>
                        <th>Updated in System</th>
                        <td id="dsupdatedat">{{ $ds->updated_at }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive" style="overflow: unset">
                <table class="table table-bordered">
                    <tr>
                        <th class="w-25">Subject</th>
                        <td>
                            @for ($i = 0; $i < $ds->subject_keywords_count; $i++)
                                @if ($i == $ds->subject_keywords_count - 1)
                                    {{ $ds->subjectKeywords[$i]->keyword }}
                                @else
                                    {{ $ds->subjectKeywords[$i]->keyword }},
                                @endif
                            @endfor
                        </td>
                    </tr>
                    <tr>
                        <th>Creator</th>
                        <td>{{ $ds->creator }}</td>
                    </tr>
                    <tr>
                        <th>Publisher</th>
                        <td>{{ $ds->publisher }}</td>
                    </tr>
                    <tr>
                        <th>Contact</th>
                        <td>{{ $ds->contact }}</td>
                    </tr>
                    <tr>
                        <th>Citation</th>
                        <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->citation) !!}</td>
                    </tr>
                    <tr>
                        <th>DOI</th>
                        <td id="doi">{{ $ds->doi }}</td>
                    </tr>
                    <tr>
                        <th>Source URL</th>
                        <td id="source_url">{{ $ds->source_url }}</td>
                    </tr>
                    <tr>
                        <th>Linkback</th>
                        <td id="linkback">{{ $ds->linkback }}</td>
                    </tr>
                    <tr>
                        <th>Date From</th>
                        <td>{{ $ds->temporal_from }}</td>
                    </tr>
                    <tr>
                        <th>Date To</th>
                        <td>{{ $ds->temporal_to }}</td>
                    </tr>
                    <tr>
                        <th>Image</th>
                        <td>
                            @if ($ds->image_path)
                                <img src="{{ asset('storage/images/' . $ds->image_path) }}" alt="Layer Image"
                                    style="max-width: 100%; max-height:150px">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th class="w-25">Latitude From</th>
                        <td>{{ $ds->latitude_from }}</td>
                    </tr>
                    <tr>
                        <th>Longitude From</th>
                        <td>{{ $ds->longitude_from }}</td>
                    </tr>
                    <tr>
                        <th>Latitude To</th>
                        <td>{{ $ds->latitude_to }}</td>
                    </tr>
                    <tr>
                        <th>Longitude To</th>
                        <td>{{ $ds->longitude_to }}</td>
                    </tr>
                    <tr>
                        <th>Language</th>
                        <td>{{ $ds->language }}</td>
                    </tr>
                    <tr>
                        <th>License</th>
                        <td>{{ $ds->license }}</td>
                    </tr>
                    <tr>
                        <th>Usage Rights</th>
                        <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->rights) !!}</td>
                    </tr>
                    <tr>
                        <th>Date Created (externally)</th>
                        <td>{{ $ds->created }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    @if ($ds->pivot->dsrole == 'OWNER' || $ds->pivot->dsrole == 'ADMIN' || $ds->pivot->dsrole == 'COLLABORATOR')
        <!-- Modal Add to dataset button -->
        @include('modals.addtodatasetmodal', ['dataset' => $ds])

        <!-- MODAL Bulk Add Dataset button -->
        @include('modals.bulkaddtodatasetmodal')

        <!-- Modal edit dataitem modal -->
        @include('modals.editdataitemmodal', ['dataset' => $ds])

        <!-- Modal delete dataitem modal -->
        @include('modals.deleteconfirmmodal')
    @endif

    <!-- Dataitem Table -->
    <div class="container-fluid">
        <div class="place-list">
            @foreach ($ds->dataitemsWithRoute as $data)
                <div class="row" data-id="{{ $data->id }}">
                    <div class="col dragIcon" style="max-width: 4%;display:none">
                        <img src="{{ asset('img/draggable.svg') }}">
                    </div>
                    <div class="col col-xl-2">
                        <h4>
                            @if ($ds->public)
                                <button type="button" class="btn btn-primary btn-sm"
                                    onclick="copyLink('{{ $data->uid }}',this,'id')">C</button>
                                <a
                                    href="{{ config('app.url') }}/places/{{ \TLCMap\Http\Helpers\UID::create($data->id, 't') }}">
                            @endif
                            @if (isset($data->title))
                                {{ $data->title }}@else{{ $data->placename }}
                            @endif
                            @if ($ds->public)
                                </a>
                            @endif
                        </h4>
                        <dl>
                            @if (isset($data->placename))
                                <dt>Placename</dt>
                                <dd>{{ $data->placename }}</dd>
                            @endif
                            @if (isset($data->recordtype_id))
                                <dt>Type</dt>
                                <dd>{{ $data->recordtype->type }}</dd>
                            @elseif(isset($data->dataset->recordtype_id))
                                <dt>Type</dt>
                                <dd>{{ $data->dataset->recordtype->type }}</dd>
                            @endif
                            <div class="dropdown mb-3">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    🌏 View Maps...
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    @if (!empty(config('app.views_root_url')) && $ds->public)
                                        <a class="dropdown-item grab-hover"
                                            onclick="window.open(`{{ config('app.views_root_url') }}/3d.html?load={{ urlencode(config('app.url') . '/places/' . \TLCMap\Http\Helpers\UID::create($data->id, 't') . '/json') }}`)">3D
                                            Viewer</a>
                                    @endif
                                    <a class="dropdown-item grab-hover"
                                        onclick="window.open('https\:\/\/www.google.com/maps/search/?api=1&query={{ $data->latitude }},{{ $data->longitude }}')">Google
                                        Maps</a>
                                    @if (isset($data->placename))
                                        <a class="dropdown-item grab-hover" target="_blank"
                                            href="https://trove.nla.gov.au/search?keyword={{ $data->placename }}">Trove
                                            Search</a>
                                    @else<a class="dropdown-item grab-hover" target="_blank"
                                            href="https://trove.nla.gov.au/search?keyword={{ $data->title }}">Trove
                                            Search</a>
                                    @endif

                                </div>
                            </div>
                            <div class="mb-3">
                                <button type="button" data-item-id="{{ $data->id }}"
                                    data-set-id="{{ $ds->id }}"
                                    class="btn btn-primary edit-dataitem-button">Edit</button>
                                <button type="button" data-item-id="{{ $data->id }}"
                                    data-set-id="{{ $ds->id }}"
                                    class="btn btn-default delete-dataitem-button">Delete</button>
                            </div>
                        </dl>
                    </div>
                    <div class="col col-xl-2">

                        <h4>Details</h4>

                        @if (isset($data->latitude))
                            <dt>Latitude</dt>
                            <dd>{{ $data->latitude }}</dd>
                        @endif
                        @if (isset($data->longitude))
                            <dt>Longitude</dt>
                            <dd>{{ $data->longitude }}</dd>
                        @endif
                        @if (isset($data->datestart))
                            <dt>Start Date</dt>
                            <dd>{{ $data->datestart }}</dd>
                        @endif
                        @if (isset($data->dateend))
                            <dt>End Date</dt>
                            <dd>{{ $data->dateend }}</dd>
                        @endif

                        @if (isset($data->state))
                            <dt>State</dt>
                            <dd>{{ $data->state }}</dd>
                        @endif
                        @if (isset($data->lga))
                            <dt>LGA</dt>
                            <dd>{{ $data->lga }}</dd>
                        @endif
                        @if (isset($data->parish))
                            <dt>Parish</dt>
                            <dd>{{ $data->parish }}</dd>
                        @endif
                        @if (isset($data->feature_term))
                            <dt>Feature Term</dt>
                            <dd>{{ $data->feature_term }}</dd>
                        @endif

                    </div>
                    <div class="col col-xl-2">
                        <h4>Description</h4>
                        @if (isset($data->description))
                            <div>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($data->description) !!}</div>
                        @endif
                    </div>
                    @if ($data->recordtype->type === 'Mobility')
                        <div class="col col-xl-2">
                            <h4>Mobility Details</h4>
                            @if (isset($data->quantity))
                                <dt>Quantity</dt>
                                <dd>{{ $data->quantity }}</dd>
                            @endif
                            @if (isset($data->route_id))
                                <dt>Route ID</dt>
                                <dd>{{ $data->route_id }}</dd>
                                @if (isset($data->stop_idx))
                                    <dt>Route Stop Number</dt>
                                    <dd>{{ $data->stop_idx }}</dd>
                                @endif
                                @if (isset($data->route_title))
                                    <dt>Route Title</dt>
                                    <dd>{{ $data->route_title }}</dd>
                                @endif
                                @if (isset($data->route_description))
                                    <dt>Route Description</dt>
                                    <dd>{{ $data->route_description }}</dd>
                                @endif
                            @endif
                        </div>
                    @endif
                    <div class="col col-xl-2">
                        <h4>Extended Data</h4>
                        @if (isset($data->extended_data))
                            {!! $data->extDataAsHTML() !!}
                        @endif
                    </div>
                    <div class="col col-xl-2">
                        <h4>Sources</h4>
                        @if (isset($data->uid))
                            <dt>TLCMap ID</dt>
                            <dd>{{ $data->uid }}</dd>
                        @endif
                        @if (isset($data->external_url))
                            <dt>Linkback</dt>
                            <dd><a href="{{ $data->external_url }}">{{ $data->external_url }}</a></dd>
                        @endif
                        @if (isset($data->source))
                            <dt>Source</dt>
                            <dd>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($data->source) !!}</dd>
                        @endif

                        @if (isset($data->created_at))
                            <dt>Created At</dt>
                            <dd>{{ $data->created_at }}</dd>
                        @endif
                        @if (isset($data->updated_at))
                            <dt id="updatedat">Updated At</dt>
                            <dd>{{ $data->updated_at }}</dd>
                        @endif

                    </div>
                    @if (!empty($data->image_path))
                        <div class="col col-xl-2">
                            <img src="{{ asset('storage/images/' . $data->image_path) }}" alt="Place image"
                                style="max-width: 100%;max-height:150px">
                        </div>
                    @endif
                    <!-- end bootstrap row -->
                </div>
            @endforeach
        </div>
        <!-- end bootstrap container -->
    </div>

    <a href="{{ url('myprofile/mydatasets') }}" class="mt-3 mb-3 btn btn-primary">Back</a>
@endsection
