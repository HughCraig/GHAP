@extends('templates.layout')

@push('scripts')
<script>
    var ajaxdbscan = "{{ url('ajaxdbscan') }}";
    var ajaxkmeans = "{{ url('ajaxkmeans') }}";
    var viewsRootUrl = "{{ config('app.views_root_url') }}";
    var currentUrl = "{{ url('/layers/' . $ds->id . '/clusteranalysis') }}";
</script>
<script src="{{ asset('/js/stmetrics-csv-download.js') }}"></script>
<script src="{{ asset('/js/clusteranalysis.js') }}"></script>
@endpush

@section('content')
<div class="container mt-4">
    <h2>Cluster Analysis</h2>
    <input type="hidden" id="csrfToken" value="{{ csrf_token() }}">
    <input type="hidden" id="ds_id" value="{{ $ds->id }}" />

    <p class="pt-4">To understand this analysis, check the <a href="{{ config('app.tlcmap_doc_url') }}/help/guides/guide/">Guide</a></p>

    <!-- Clustering Options Form -->
    <div class="user-input">
        <div class="form-group">
            <label for="clusteringMethod">Clustering Method:</label>
            <select class="form-control" id="clusteringMethod">
                <option value="dbscan">DBScan</option>
                <option value="kmeans">KMeans</option>
            </select>
        </div>

        <!-- DBScan Inputs -->
        <div class="form-group dbscan-input">
            <label for="distance">Distance (kms):</label>
            <input type="number" class="form-control" id="distance" placeholder="Enter distance in kilometers" value="3">
        </div>
        <div class="form-group dbscan-input">
            <label for="minPoints">Number of Neighbours:</label>
            <input type="number" class="form-control" id="minPoints" placeholder="Enter minimum number of neighbors" value="0">
        </div>

        <!-- KMeans Inputs -->
        <div class="form-group kmeans-input" style="display:none;">
            <label for="numClusters">Number of Clusters:</label>
            <input type="number" class="form-control" id="numClusters" placeholder="Enter number of clusters" value="1">
        </div>
        <div class="form-group kmeans-input" style="display:none;">
            <label for="withinRadius">Within Radius (kms):</label>
            <input type="number" class="form-control" id="withinRadius" placeholder="Enter maximum radius in kilometers">
        </div>

        <button id="cluster_analysis" class="btn btn-primary">Analyze</button>
    </div>

    <div class="result-output" style="display: none;">
        <button class="btn btn-secondary" id="backButton">Back</button>

        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="visualiseDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                View Maps
            </button>
            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                <a class="dropdown-item grab-hover" id="collection-3d-map">3D Viewer</a>
                <a class="dropdown-item grab-hover" id="collection-cluster-map">Cluster</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Download
            </button>
            <div class="dropdown-menu" aria-labelledby="downloadDropdown">
                <a class="dropdown-item grab-hover" id="cluster-download-csv">CSV</a>
                <a class="dropdown-item grab-hover" id="cluster-download-json">GeoJSON</a>
                <a class="dropdown-item grab-hover" id="cluster-download-kml">KML</a>
            </div>
        </div>

        <div class="result-table"></div>
    </div>

</div>
@endsection