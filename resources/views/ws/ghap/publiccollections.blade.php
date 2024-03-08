@extends('templates.layout')

@push('scripts')
    <script src="{{ asset('js/publiccollections.js') }}"></script>
@endpush

@section('content')
    <h2>
        Multilayers
        <span tabindex="0" data-html="true"
              data-animation="true"
              class="glyphicon glyphicon-question-sign"
              data-toggle="tooltip"
              data-placement="right"
              style="font-size:16px"
              title="Multilayers are contributed by many authors. They may be incomplete or imprecise. Check the multilayer for details about the source.">
        </span>
    </h2>
    <a href="{{route('index')}}" class="mb-3 btn btn-primary">Back</a>
    <a href="{{url('myprofile/mycollections/newcollection')}}" class="mb-3 btn btn-primary">Create Multilayer</a><br>

    <table id="collectionsTable" class="display" style="width:100%">
        <thead class="w3-black"><tr><th>Name</th><th>Size</th><th>Content Warning</th><th>Created</th><th>Updated</th><th>View Map</th></tr></thead>
        <tbody>
        @foreach($collections as $collection)
            <tr id="row_id_{{$collection->id}}">
                <td><a href="{{url()->full()}}/{{$collection->id}}">{{ $collection->name }}</a></td>
                <td>{{count($collection->datasets) + count($collection->savedSearches)}}</td>
                <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($collection->warning) !!}</td>
                <td>{{ $collection->created_at }}</td>
                <td>{{ $collection->updated_at }}</td>
                <td>
                    @if (!empty(config('app.views_root_url')))
                        <!-- Visualise-->
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                🌏 View Maps...
                            </button>
                            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-3d.html?load=' + encodeURIComponent('{{url()->full()}}/{{$collection->id}}/json'))">3D Viewer</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-cluster.html?load=' + encodeURIComponent('{{url()->full()}}/{{$collection->id}}/json'))">Cluster</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-journey.html?load=' + encodeURIComponent('{{url()->full()}}/{{$collection->id}}/json?line=route'))">Journey Route</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-journey.html?load=' + encodeURIComponent('{{url()->full()}}/{{$collection->id}}/json?line=time'))">Journey Times</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-timeline.html?load=' + encodeURIComponent('{{url()->full()}}/{{$collection->id}}/json?sort=start'))">Timeline</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-werekata.html?load=' + encodeURIComponent('{{url()->full()}}/{{$collection->id}}/json'))">Werekata Flight by Route</a>
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-werekata.html?load=' + encodeURIComponent('{{url()->full()}}/{{$collection->id}}/json?sort=start'))">Werekata Flight by Time</a>
                            </div>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <a href="{{route('index')}}" class="mb-3 btn btn-primary">Back</a>
@endsection
