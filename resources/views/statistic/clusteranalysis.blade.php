@extends('templates.layout')

@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    var ajaxdbscan = "{{ url('ajaxdbscan') }}";
    var ajaxkmeans = "{{ url('ajaxkmeans') }}";
    var viewsRootUrl = "{{ config('app.views_root_url') }}";
    var currentUrl = "{{ url()->full() }}";
</script>
<script src="{{ asset('/js/clusteranalysis.js') }}"></script>
@endpush

@section('content')
<div class="container mt-4">
    <h2>Clustering Analysis</h2>
    <input type="hidden" id="ds_id" value="{{ $ds->id }}" />

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
        <button class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false" id="mapViewButton">
            🌏 View Map
        </button>
        <button class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false" id="downloadCsvButton">
            Download CSV
        </button>
        <div class="result-table"></div>
    </div>

</div>
@endsection