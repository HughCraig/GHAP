@extends('templates.layout')

@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    var ajaxclosenessanalysis = "{{ url('ajaxclosenessanalysis') }}";
    var layers = {!! $layers !!};
    var viewsRootUrl = "{{ config('app.views_root_url') }}";
    var currentUrl = "{{ url()->full() }}";
</script>

<script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
<script src="{{ asset('/js/closenessanalysis.js') }}"></script>
@endpush

@section('content')
<div class="container mt-4">
    <h2>Closeness Analysis</h2>
    <input type="hidden" id="ds_id" value="{{ $ds->id }}" />

    <!-- Closeness Analysis Options Form -->
    <div class="user-input">

        <div>Current Layer: {{ $ds -> name}}</div>
        <div class="align-items-center my-auto mb-2" style="display: flex;">
            <div>Targer Layer:</div>
            <div class="pt-2 pl-4" style="width: 60%;">
                <input type="text" class="w3-white form-control" id="searchlayer" autocomplete="off">
                <input type="hidden" name="searchlayer" id="selected-layer-id">
            </div>
        </div>

        <button id="closeness_analysis" class="btn btn-primary">Analysis</button>
    </div>

    <div>

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