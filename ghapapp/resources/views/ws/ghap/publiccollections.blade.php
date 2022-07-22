@extends('templates.layout')

@section('content')
    <script>
      $(document).ready( function () {
            $("#collectionsTable").dataTable({
                orderClasses: false,
                bPaginate: true,
                bFilter: true,
                bInfo: false,
                bSortable: true,
                bRetrieve: true,
                aaSorting: [[ 0, "asc" ]], 
                "pageLength": 25
            }); 
        });
    </script>

    <h2>Multilayers</h2>
    <a href="{{route('index')}}" class="mb-3 btn btn-primary">Back</a><br>

    <table id="collectionsTable" class="display" style="width:100%">
        <thead class="w3-black"><tr><th>Name</th><th>Size</th><th>Content Warning</th><th>Created</th><th>Updated</th><th>View Map</th></tr></thead>
        <tbody>
        @foreach($collections as $collection)
            <tr id="row_id_{{$collection->id}}">
                <td><a href="{{url()->full()}}/{{$collection->id}}">{{ $collection->name }}</a></td>
                <td>{{ count($collection->datasets) }}</td>
                <td>{{ $collection->warning }}</td>
                <td>{{ $collection->created_at }}</td>
                <td>{{ $collection->updated_at }}</td>
                <td>
                    <!-- Visualise-->
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        🌏 View Maps...
                        </button>
                        <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/collection-3d.html?load={{url()->full()}}/{{$collection->id}}/json')">3D Viewer</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/collection-cluster.html?load={{url()->full()}}/{{$collection->id}}/json')">Cluster</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/collection-journey.html?line=route&load={{url()->full()}}/{{$collection->id}}/json')">Journey Route</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/collection-journey.html?line=time&load={{url()->full()}}/{{$collection->id}}/json')">Journey Times</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/collection-timeline.html?load={{url()->full()}}/{{$collection->id}}/json')">Timeline</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/collection-werekata.html?&load={{url()->full()}}/{{$collection->id}}/json')">Werekata Flight by Route</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('/view/collection-werekata.html?sort=start&load={{url()->full()}}/{{$collection->id}}/json')">Werekata Flight by Time</a>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <a href="{{route('index')}}" class="mb-3 btn btn-primary">Back</a>
@endsection
