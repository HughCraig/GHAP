@extends('templates.layout')


@push('scripts')
<script>
    const parsetexturl = "{{url('ajaxparsetext')}}";
    const textId = "{{ $text->id }}";
</script>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="{{ asset('js/userparsetext.js') }}"></script>
@endpush

@section('content')

<h2>Parse '{{ $text->name }}'</h2>
<input type="hidden" id="csrfToken" value="{{ csrf_token() }}">

<div class="row pt-4">
    <div class="col-lg-4">
        <h4 class="font-weight-bold">Parsing Method</h4>
        <select id="parsing_method" class="mb-4 w3-white form-control">
            <option value="BERT">BERT</option>
            <option value="Dictionary">Dictionary</option>
            <option value="Dictionary with coordinates">Dictionary with coordinates</option>
        </select>
    </div>

    <div class="col-lg-4">
        <h4 class="font-weight-bold">Geocoding Method</h4>
        <select id="geocoding_method" class="mb-4 w3-white form-control">
            <option value="Google Geocoding API">Google Geocoding API</option>
            <option value="Mordecai">Mordecai</option>
        </select>
    </div>

    <div class="col-lg-4">
        <h4 class="font-weight-bold">Geocoding Bias</h4>
        <select id="geocoding_bias" class="mb-4 w3-white form-control">

        </select>
    </div>
</div>

<div class="row">
    <div class="col">
        <h4 class="font-weight-bold">Dictionary</h4>
        <input type="file" id="dictionary" />
    </div>
</div>

<div class="btn btn-primary mt-4" id="parse_text_submit">Parse</div>

@endsection