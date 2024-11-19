@extends('templates.layout')

@push('styles')
<link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
<link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
@endpush


@push('scripts')
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
    const parsetexturl = "{{url('ajaxparsetext')}}";
    const textId = "{{ $text->id }}";
    const ajaxadddataitem = "{{url('ajaxadddataitem')}}";
    const ajaxaddtextcontent = "{{url('ajaxaddtextcontent')}}";
    const ajaxgetdataitemmaps = "{{url('ajaxgetdataitemmaps')}}";
</script>

<script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
<script src="{{ asset('js/message-banner.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>

<script type="text/javascript" src="{{ asset('js/addnewdatasetmodal.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/userparsetext.js') }}"></script>
@endpush

@section('content')

<h2>Parse '{{ $text->name }}'</h2>
<input type="hidden" id="csrfToken" value="{{ csrf_token() }}">

@auth
@include('modals.addnewdatasetmodal')
@endauth

<div class="row pt-4">
    <div class="col-lg-4">
        <h4 class="font-weight-bold">Parsing Method</h4>
        <select id="parsing_method" class="mb-4 w3-white form-control">
            <option value="bert">BERT</option>
            <option value="dictionary">Dictionary</option>
            <option value="dictionary_with_coords">Dictionary with coordinates</option>
        </select>
    </div>

    <div class="col-lg-4">
        <h4 class="font-weight-bold">Geocoding Method</h4>
        <select id="geocoding_method" class="mb-4 w3-white form-control">
            <option value="google_geocoding">Google Geocoding</option>
        </select>
    </div>

    <div class="col-lg-4">
        <h4 class="font-weight-bold">Geocoding Bias</h4>
        <select id="geocoding_bias" class="mb-4 w3-white form-control">
            <option value="Australia">Australia</option>
            <option value="USA">USA</option>
        </select>
    </div>
</div>

<div class="row" id="dictionary_file_input" style="display: none;">
    <div class="col">
        <h4 class="font-weight-bold">Dictionary</h4>
        <input type="file" id="dictionary" />
    </div>
</div>

<div class="btn btn-primary mt-4" id="parse_text_submit">Parse</div>

<div id="parse_result" style="display:none">
    <div class="place-list pt-4">
    </div>

    <div class="d-flex">
        <div class="btn btn-primary mt-4" id="select_all">Select All</div>
        <div class="btn btn-primary mt-4 ml-4" id="select_none">Select None</div>
    </div>

    <div class="btn btn-primary mt-4" id="add_to_new_layer">Add to New Layer</div>
</div>


<div id="loadingWheel">
    <div class="spinner"></div>
    <div class="loading-text"></div>
</div>

@endsection