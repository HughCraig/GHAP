@extends('templates.layout')

@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    var ajaxtemporalclustering = "{{ url('ajaxtemporalclustering') }}";
    var viewsRootUrl = "{{ config('app.views_root_url') }}";
    var currentUrl = "{{ url()->full() }}";
</script>

<script src="{{ asset('/js/temporalclustering.js') }}"></script>
@endpush

@section('content')
<div class="container mt-4">
    <h2>Temporal Clustering</h2>
    <input type="hidden" id="ds_id" value="{{ $ds->id }}" />

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