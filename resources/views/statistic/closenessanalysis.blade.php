@extends('templates.layout')

@push('scripts')
<script>
    var ajaxclosenessanalysis = "{{ url('ajaxclosenessanalysis') }}";
    var layers = {!! $layers !!};
    var viewsRootUrl = "{{ config('app.views_root_url') }}";
    var currentUrl = "{{ url('/layers/' . $ds->id . '/closenessanalysis') }}";
</script>

<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<script src="{{ asset('/js/stmetrics-csv-download.js') }}"></script>
<script src="{{ asset('/js/closenessanalysis.js') }}"></script>
@endpush

@section('content')
<div class="container mt-4">
    <h2>Closeness Analyse</h2>
    <input type="hidden" id="csrfToken" value="{{ csrf_token() }}">
    <input type="hidden" id="ds_id" value="{{ $ds->id }}" />

    <p class="pt-4 pb-4">To understand this analysis, check the <a href="https://tlcmap.org/help/guides/ghap-guide/">GHAP Guide</a></p>

    <!-- Closeness Analyse Options Form -->
    <div class="user-input">

        <div>Current Layer: {{ $ds -> name}}</div>
        <div class="align-items-center my-auto mb-2" style="display: flex;">
            <div>Targer Layer:</div>
            <div class="pt-2 pl-4" style="width: 60%;">
                <input type="text" class="w3-white form-control" id="searchlayer" autocomplete="off">
                <input type="hidden" name="searchlayer" id="selected-layer-id">
            </div>
        </div>

        <button id="closeness_analysis" class="btn btn-primary">Analyse</button>
    </div>

    <div class="result-output pt-4" style="display: none;">

        <button class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false" id="mapViewButton">
            🌏 View Map
        </button>
        <button class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false" id="downloadCsvButton">
            Download CSV
        </button>

        <div class="result-table pt-4">
        </div>
    </div>

</div>
@endsection