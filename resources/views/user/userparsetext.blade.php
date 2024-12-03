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
    const textTitle = "{{ $text->name }}";
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
        <h4 class="font-weight-bold">Geoparsing Method</h4>
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
            <option value="null">Global</option>
            <option value="Algeria">Algeria</option>
            <option value="Angola">Angola</option>
            <option value="Afghanistan">Afghanistan</option>
            <option value="Armenia">Armenia</option>
            <option value="Argentina">Argentina</option>
            <option value="Benin">Benin</option>
            <option value="Botswana">Botswana</option>
            <option value="Burundi">Burundi</option>
            <option value="Bahrain">Bahrain</option>
            <option value="Bangladesh">Bangladesh</option>
            <option value="Brunei">Brunei</option>
            <option value="Belarus">Belarus</option>
            <option value="Belgium">Belgium</option>
            <option value="Bahamas">Bahamas</option>
            <option value="Barbados">Barbados</option>
            <option value="Belize">Belize</option>
            <option value="Brazil">Brazil</option>
            <option value="Bolivia">Bolivia</option>
            <option value="Chad">Chad</option>
            <option value="Comoros">Comoros</option>
            <option value="Egypt">Egypt</option>
            <option value="Liberia">Liberia</option>
            <option value="Libya">Libya</option>
            <option value="Mauritius">Mauritius</option>
            <option value="Niger">Niger</option>
            <option value="Nigeria">Nigeria</option>
            <option value="Somalia">Somalia</option>
            <option value="South Africa">South Africa</option>
            <option value="Sudan">Sudan</option>
            <option value="Tanzania">Tanzania</option>
            <option value="Togo">Togo</option>
            <option value="Tunisia">Tunisia</option>
            <option value="Uganda">Uganda</option>
            <option value="Zambia">Zambia</option>
            <option value="Bhutan">Bhutan</option>
            <option value="Cambodia">Cambodia</option>
            <option value="China">China</option>
            <option value="Cyprus">Cyprus</option>
            <option value="Georgia">Georgia</option>
            <option value="India">India</option>
            <option value="Indonesia">Indonesia</option>
            <option value="Iran">Iran</option>
            <option value="Iraq">Iraq</option>
            <option value="Israel">Israel</option>
            <option value="Japan">Japan</option>
            <option value="Laos">Laos</option>
            <option value="Malaysia">Malaysia</option>
            <option value="Maldives">Maldives</option>
            <option value="Mongolia">Mongolia</option>
            <option value="Nepal">Nepal</option>
            <option value="North Korea">North Korea</option>
            <option value="Oman">Oman</option>
            <option value="Pakistan">Pakistan</option>
            <option value="Philippines">Philippines</option>
            <option value="Singapore">Singapore</option>
            <option value="South Korea">South Korea</option>
            <option value="Syria">Syria</option>
            <option value="Thailand">Thailand</option>
            <option value="Turkey">Turkey</option>
            <option value="United Arab Emirates">United Arab Emirates</option>
            <option value="Uzbekistan">Uzbekistan</option>
            <option value="Vietnam">Vietnam</option>
            <option value="Yemen">Yemen</option>
            <option value="Croatia">Croatia</option>
            <option value="Czech Republic (Czechia)">Czech Republic (Czechia)</option>
            <option value="Denmark">Denmark</option>
            <option value="Estonia">Estonia</option>
            <option value="Finland">Finland</option>
            <option value="France">France</option>
            <option value="Germany">Germany</option>
            <option value="Greece">Greece</option>
            <option value="Iceland">Iceland</option>
            <option value="Ireland">Ireland</option>
            <option value="Italy">Italy</option>
            <option value="Latvia">Latvia</option>
            <option value="Norway">Norway</option>
            <option value="Poland">Poland</option>
            <option value="Portugal">Portugal</option>
            <option value="Romania">Romania</option>
            <option value="Russia">Russia</option>
            <option value="Serbia">Serbia</option>
            <option value="Slovakia">Slovakia</option>
            <option value="Spain">Spain</option>
            <option value="Sweden">Sweden</option>
            <option value="Switzerland">Switzerland</option>
            <option value="Ukraine">Ukraine</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="Canada">Canada</option>
            <option value="Cuba">Cuba</option>
            <option value="Dominica">Dominica</option>
            <option value="Grenada">Grenada</option>
            <option value="Guatemala">Guatemala</option>
            <option value="Haiti">Haiti</option>
            <option value="Honduras">Honduras</option>
            <option value="Jamaica">Jamaica</option>
            <option value="Mexico">Mexico</option>
            <option value="Nicaragua">Nicaragua</option>
            <option value="Panama">Panama</option>
            <option value="Saint Lucia">Saint Lucia</option>
            <option value="United States of America">United States of America</option>
            <option value="Fiji">Fiji</option>
            <option value="Micronesia">Micronesia</option>
            <option value="Nauru">Nauru</option>
            <option value="New Zealand">New Zealand</option>
            <option value="Palau">Palau</option>
            <option value="Samoa">Samoa</option>
            <option value="Tonga">Tonga</option>
            <option value="Tuvalu">Tuvalu</option>
            <option value="Vanuatu">Vanuatu</option>
            <option value="Chile">Chile</option>
            <option value="Colombia">Colombia</option>
            <option value="Ecuador">Ecuador</option>
            <option value="Guyana">Guyana</option>
            <option value="Paraguay">Paraguay</option>
            <option value="Peru">Peru</option>
            <option value="Suriname">Suriname</option>
            <option value="Uruguay">Uruguay</option>
            <option value="Venezuela">Venezuela</option>

        </select>
    </div>
</div>

<div class="row" id="dictionary_file_input" style="display: none;">
    <div class="col">
        <h4 class="font-weight-bold">Dictionary</h4>
        <input type="file" id="dictionary" />
        <p id="dictionary_file_instructions" style="color: grey; font-size: 14px; margin-top: 5px; display: none;"></p>
    </div>
</div>

<label data-toggle="tooltip" class="d-flex datasource-filter btn pl-0">
    Save to new layer automatically. Novel length texts may take an hour or so. You can close the browser and return if results are saved
    <input type="checkbox" id="saveautomatically" class="ml-2" style="margin-top: 1px; cursor:pointer">
</label>

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