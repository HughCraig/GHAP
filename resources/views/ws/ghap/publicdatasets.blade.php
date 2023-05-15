@extends('templates.layout')

@push('scripts')
    <script src="{{ asset('js/publicdatasets.js') }}"></script>
@endpush

@section('content')

    <h2>
        Layers
        <span tabindex="0" data-html="true"
              data-animation="true"
              class="glyphicon glyphicon-question-sign"
              data-toggle="tooltip"
              data-placement="right"
              style="font-size:16px"
              title="Layers are contributed by many authors. They may be incomplete or imprecise. Check the layer for details about the source.">
        </span>
    </h2>

    <a href="{{route('index')}}" class="mb-3 btn btn-primary">Back</a>
    <a href="{{url('myprofile/mydatasets/newdataset')}}" class="mb-3 btn btn-primary">Create Layer</a><br>

    <table id="datasettable" class="display" style="width:100%">
        <thead class="w3-black"><tr><th>Name</th><th>Size</th><th>Type</th><th>Content Warning</th><th>Created</th><th>Updated</th><th>View Map</th></tr></thead>
        <tbody>
        @foreach($datasets as $ds)
            <tr id="row_id_{{$ds->id}}">
                <td><a href="{{url()->full()}}/{{$ds->id}}">{{$ds->name}}</a></td>
                <td>{{count($ds->dataitems)}}</td>
                <td>{{$ds->recordtype->type}}</td>
                <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->warning) !!}</td>
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
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load={{url()->full()}}/{{$ds->id}}/json')">3D Viewer</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load={{url()->full()}}/{{$ds->id}}/json')">Cluster</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?line=route&load={{url()->full()}}/{{$ds->id}}/json')">Journey Route</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?line=time&load={{url()->full()}}/{{$ds->id}}/json')">Journey Times</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/timeline.html?load={{url()->full()}}/{{$ds->id}}/json?sort=start')">Timeline</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?&load={{url()->full()}}/{{$ds->id}}/json')">Werekata Flight by Route</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?sort=start&load={{url()->full()}}/{{$ds->id}}/json')">Werekata Flight by Time</a>
                                @if (!empty(config('app.views_temporal_earth_url')))
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file={{url()->full()}}/{{$ds->id}}/kml')">Temporal Earth</a>
                                @endif
                            </div>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a>
@endsection
