@extends('templates.layout')

@push('scripts')
    <script src="{{ asset('js/publiccollection.js') }}"></script>
@endpush

@section('content')

    <h2>Multilayer</h2>

    @if (!empty(config('app.views_root_url')))
        <!-- Visualise-->
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            🌏 View Maps...
            </button>
            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-3d.html?load={{url()->full()}}/json')">3D Viewer</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-cluster.html?load={{url()->full()}}/json')">Cluster</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-journey.html?line=route&load={{url()->full()}}/json')">Journey Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-journey.html?line=time&load={{url()->full()}}/json')">Journey Times</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-timeline.html?load={{url()->full()}}/json')">Timeline</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-werekata.html?load={{url()->full()}}/json')">Werekata Flight by Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-werekata.html?sort=start&load={{url()->full()}}/json')">Werekata Flight by Time</a>
            </div>
        </div>
    @endif

    <!-- Quick Info -->
    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th class="w-25">Name</th><td>{{$collection->name}}</td></tr>
                    <tr style="height: 50px; overflow: auto"><th>Description</th><td>{{$collection->description}}</td></tr>
                    <tr style="height: 50px; overflow: auto"><th>Content Warning</th><td>{{$collection->warning}}</td></tr>
                    <tr><th>Contributor</th><td>{{$collection->ownerUser->name}}</td></tr>
                    <tr><th>Entries</th><td id="collectionCount">{{count($collection->datasets)}}</td></tr>
                    <tr><th>Added to System</th><td>{{$collection->created_at}}</td></tr>
                    <tr><th>Updated in System</th><td id="collectionUpdatedAt">{{$collection->updated_at}}</td></tr>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive" style="overflow: unset">
                <table class="table table-bordered">
                    <tr><th class="w-25">Subject</th>
                        <td>
                            @for ($i = 0; $i < count($collection->subjectKeywords); $i++)
                                @if ($i == count($collection->subjectKeywords) - 1)
                                    {{$collection->subjectKeywords[$i]->keyword}}
                                @else
                                    {{$collection->subjectKeywords[$i]->keyword}},
                                @endif
                            @endfor
                        </td>
                    </tr>
                    <tr><th>Creator</th><td>{{$collection->creator}}</td></tr>
                    <tr><th>Publisher</th><td>{{$collection->publisher}}</td></tr>
                    <tr><th>Contact</th><td>{{$collection->contact}}</td></tr>
                    <tr><th>Citation</th><td>{{$collection->citation}}</td></tr>
                    <tr><th>DOI</th><td id="doi">{{$collection->doi}}</td></tr>
                    <tr><th>Source URL</th><td id="sourceURL">{{$collection->source_url}}</td></tr>
                    <tr><th>Linkback</th><td id="linkback">{{$collection->linkback}}</td></tr>
                    <tr><th>Date From</th><td>{{$collection->temporal_from}}</td></tr>
                    <tr><th>Date To</th><td>{{$collection->temporal_to}}</td></tr> 
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th class="w-25">Latitude From</th><td>{{$collection->latitude_from}}</td></tr>
                    <tr><th>Longitude From</th><td>{{$collection->longitude_from}}</td></tr>
                    <tr><th>Latitude To</th><td>{{$collection->latitude_to}}</td></tr>
                    <tr><th>Longitude To</th><td>{{$collection->longitude_to}}</td></tr>
                    <tr><th>Language</th><td>{{$collection->language}}</td></tr>
                    <tr><th>License</th><td>{{$collection->license}}</td></tr>
                    <tr><th>Rights</th><td>{{$collection->rights}}</td></tr>
                    <tr><th>Date Created (externally)</th><td>{{$collection->created}}</td></tr>
                </table>
            </div>
        </div>
    </div>

    @if (!empty($datasets))
        <table id="datasetsTable" class="display" style="width:100%">
            <thead class="w3-black"><tr><th>Name</th><th>Size</th><th>Type</th><th>Content Warning</th><th>Created</th><th>Updated</th><th>View Map</th></tr></thead>
            <tbody>
            @foreach($datasets as $ds)
                <tr id="row_id_{{$ds->id}}">
                    <td><a href="{{url('publicdatasets')}}/{{$ds->id}}">{{$ds->name}}</a></td>
                    <td>{{count($ds->dataitems)}}</td>
                    <td>{{$ds->recordtype->type}}</td>
                    <td>{{$ds->warning}}</td>
                    <td>{{$ds->created_at}}</td>
                    <td>{{$ds->updated_at}}</td>
                    <td>
                        @if (!empty(config('app.views_root_url')))
                            <!-- Visualise-->
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    🌏 View Maps...
                                </button>
                                <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load={{url('publicdatasets')}}/{{$ds->id}}/json')">3D Viewer</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load={{url('publicdatasets')}}/{{$ds->id}}/json')">Cluster</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?line=route&load={{url('publicdatasets')}}/{{$ds->id}}/json')">Journey Route</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?line=time&load={{url('publicdatasets')}}/{{$ds->id}}/json')">Journey Times</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/timeline.html?load={{url('publicdatasets')}}/{{$ds->id}}/json?sort=start')">Timeline</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?&load={{url('publicdatasets')}}/{{$ds->id}}/json')">Werekata Flight by Route</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?sort=start&load={{url('publicdatasets')}}/{{$ds->id}}/json')">Werekata Flight by Time</a>
                                    @if (!empty(config('app.views_temporal_earth_url')))
                                        <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file={{url('publicdatasets')}}/{{$ds->id}}/kml')">Temporal Earth</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <a href="{{ url('publiccollections') }}" class="mt-3 mb-3 btn btn-primary">All Multilayers</a>

@endsection
