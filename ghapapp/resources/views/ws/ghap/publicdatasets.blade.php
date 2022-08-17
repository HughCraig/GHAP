@extends('templates.layout')

@push('scripts')
    <script src="{{ asset('js/publicdatasets.js') }}"></script>
@endpush

@section('content')

    <h2>Layers</h2>
    <a href="{{route('index')}}" class="mb-3 btn btn-primary">Back</a><br>

    <table id="datasettable" class="display" style="width:100%">
        <thead class="w3-black"><tr><th>Name</th><th>Size</th><th>Type</th><th>Content Warning</th><th>Created</th><th>Updated</th><th>View Map</th></tr></thead>
        <tbody>
        @foreach($datasets as $ds)
            <tr id="row_id_{{$ds->id}}">
                <td><a href="{{url()->full()}}/{{$ds->id}}">{{$ds->name}}</a></td>
                <td>{{count($ds->dataitems)}}</td>
                <td>{{$ds->recordtype->type}}</td>
                <td>{{$ds->warning}}</td>
                <td>{{$ds->created_at}}</td>
                <td>{{$ds->updated_at}}</td>
                <td>
                    <!-- Visualise-->
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        🌏 View Maps...
                        </button>
                        <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/3d.html?load={{url()->full()}}/{{$ds->id}}/json')">3D Viewer</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/cluster.html?load={{url()->full()}}/{{$ds->id}}/json')">Cluster</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/journey.html?line=route&load={{url()->full()}}/{{$ds->id}}/json')">Journey Route</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/journey.html?line=time&load={{url()->full()}}/{{$ds->id}}/json')">Journey Times</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/timeline.html?load={{url()->full()}}/{{$ds->id}}/json?sort=start')">Timeline</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/werekata.html?&load={{url()->full()}}/{{$ds->id}}/json')">Werekata Flight by Route</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/werekata.html?sort=start&load={{url()->full()}}/{{$ds->id}}/json')">Werekata Flight by Time</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/te/?file={{url()->full()}}/{{$ds->id}}/kml')">Temporal Earth</a>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <a href="{{url('myprofile')}}" class="mb-3 btn btn-primary">Back</a>
@endsection
