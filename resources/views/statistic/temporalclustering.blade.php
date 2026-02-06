@extends('templates.layout')

@push('scripts')
<script>
    var ajaxtemporalclustering = "{{ url('ajaxtemporalclustering') }}";
    var viewsRootUrl = "{{ config('app.views_root_url') }}";
    var currentUrl = "{{ url('/layers/' . $ds->id . '/temporalclustering') }}";
</script>

<script src="{{ asset('/js/stmetrics-csv-download.js') }}"></script>
<script src="{{ asset('/js/temporalclustering.js') }}"></script>
@endpush

@section('content')
<div class="container mt-4">
    <h2>Temporal Clustering</h2>
    <input type="hidden" id="csrfToken" value="{{ csrf_token() }}">
    <input type="hidden" id="ds_id" value="{{ $ds->id }}" />

    <p class="pt-4">To understand this analysis, check the <a href="{{ config('app.tlcmap_doc_url') }}/help/guides/guide/">Guide</a></p>

    <!-- Temporal Clustering Options Form -->
    <div class="user-input">
        <div class="form-group">
            <label for="yearsInterval">Years Interval:</label>
            <input type="number" step="any" class="form-control" id="yearsInterval" placeholder="Enter interval in years">
        </div>

        <div class="form-group">
            <label for="daysInterval">Days Interval:</label>
            <input type="number" step="any" class="form-control" id="daysInterval" placeholder="Enter interval in days">
        </div>

        <button id="temporal_cluster" class="btn btn-primary">Cluster</button>
    </div>

    <div class="result-output" style="display: none;">
        <button class="btn btn-secondary" id="backButton">Back</button>
        
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                View Maps
            </button>
            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                <a class="dropdown-item grab-hover" id="collection-3d-map">3D Viewer</a>
                <a class="dropdown-item grab-hover" id="collection-cluster-map">Cluster</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" type="button" id="downloadDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Download
            </button>
            <div class="dropdown-menu" aria-labelledby="downloadDropdown">
                <a class="dropdown-item grab-hover" id="temporal-download-csv">CSV</a>
                <a class="dropdown-item grab-hover" id="temporal-download-json">GeoJSON</a>
                <a class="dropdown-item grab-hover" id="temporal-download-kml">KML</a>
            </div>
        </div>

        <div class="result-table"></div>
    </div>

</div>
@endsection