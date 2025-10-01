@extends('templates.layout')

@push('styles')
    <link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
    <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    <style>
        .tox-tinymce {
            max-height: 200px;
        }

        .ui-datepicker-inline.ui-datepicker.ui-widget.ui-widget-content.ui-helper-clearfix.ui-corner-all {
            display: none !important;
        }

        .ui-datepicker-title{
            display: none;
        }

        .ui-datepicker-calendar{
            display: none;
        }
    </style>
@endpush

@push('scripts')
    <script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
    <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
    <script> 
        const max_upload_image_size = {{ config('app.max_upload_image_size') }};
        const ajaxcreatedataitemsfordataset =  "{{url('ajaxcreatedataitemsfordataset')}}";
        const ajaxaddtextcontent = "{{url('ajaxaddtextcontent')}}";
    </script>
    <script src="{{ asset('/js/message-banner.js') }}"></script> 
    <script src="{{ asset('js/validation.js') }}"></script>
    <script src="{{ asset('js/usernewdataset.js') }}"></script>
    <script src="{{ asset('/js/dataitem.js') }}"></script> 
    <script type="text/javascript" src="{{ asset('js/addnewdatasetmodal.js') }}"></script>
    <script src="{{ asset('/js/contribute.js') }}"></script> 

@endpush

@section('content')

    <!-- Modal Add to dataset button -->
    @include('modals.contributesourcemodal')

    <h2>
        Contribute
        @include('templates.misc.contentdisclaimer')
    </h2>

    @csrf
    <div class="p-4 mt-4 mb-5" style="border: 1.5px solid black;">

        <div class="mb-5" style="font-size: 1.5em; font-weight: 900;">
            1. Layer details
        </div>

        <div class="mb-4">
            *Layer name
            <input type="text" class="mb-2 w3-white form-control" id="layername" required />
        </div>

        <div class="mb-4">
            *Description
            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                title="A short paragraph summarising the layer. Anything not covered by other fields can be added here."></span>
            <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" id="description"></textarea>
        </div>


        <button class="m-4 p-4 btn btn-secondary" href="#layersource" data-bs-toggle="collapse" id="basicInfoNextButton" disabled>
            Next
        </button>
    </div>

    <div class="p-4 mt-4 mb-5" style="border: 1.5px solid black;">
        <div style="font-size: 1.5em; font-weight: 900;">  
            2. Source
        </div>
        
        <div id="layersource" class="collapse" class="container-fluid border">
            <button class="m-4 p-4 btn btn-primary" id="source">Source</button>

            <div class="ml-4" id="sourceadded" style="color: blue;"></div>

            <div>
                <button class="m-4 p-4 btn btn-secondary" href="#layerotherinfo" data-bs-toggle="collapse">Next</button>
            </div>
        </div>

    </div>

    <div class="p-4 mt-4 mb-5" style="border: 1.5px solid black;">

        <div style="font-size: 1.5em; font-weight: 900;">  
                3. Other information
        </div>

        <div id="layerotherinfo" class="collapse" class="container-fluid border">
            <div class="row">
                <div class="col-lg p-5">
                    Subject (keywords)
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="Type and press enter to create keywords describing this layer."></span>
                    <input id="tags" name="tags" type="text" class="smallerinputs mb-2 w3-white form-control" />

                    <label for="layerrecordtype">Record Type</label>
                    <select class="w3-white form-control mb-3" id="layerrecordtype" name="addrecordtype">
                        @foreach($recordtypes as $type)
                        <option label="{{$type}}">{{$type}}</option>
                        @endforeach
                    </select>

                    Visibility
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="TLCMap is intended for making information public, but you can set it to private while you work on it if you want. Some visualisations may not work while the layer is set to private."></span>
                    <select id="public" name="public" class="mb-4 w3-white form-control">
                        <option value="0">Private</option>
                        <option value="1" selected="selected">Public</option>
                    </select>

                    Allow ANPS to collect this data?
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="GHAP is based on information collected and compiled by the Australian National Placenames Survey, who keep records of historical and other placenames. If your layer includes placenames, we’d like to provide them back to ANPS to help their research and records."></span>
                    <select id="allowanps" name="allowanps" class="mb-4 w3-white form-control">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>

                    Creator
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="The person or organisation who researched or prepared the data."></span>
                    <input type="text" class="mb-2 w3-white form-control" id="creator" />

                </div>

                <div class="col-lg p-5">
                    Publisher
                    <input type="text" class="mb-2 w3-white form-control" id="publisher" />

                    Contact
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="Contact details if people have questions about this layer."></span>
                    <input type="text" class="mb-2 w3-white form-control" id="contact" />


                    DOI <!-- help hover button -->
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="A valid Data Object Identifier for the ‘official’ version of the information in this layer. TLCMap can be used to visualise the information, but isn’t an official research archive or data repository. You can always add a DOI later. This layer will also receive a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                    <input type="text" class="mb-4 w3-white form-control" id="doi" />

                    Source URL <!-- help hover button -->
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="The URL linking to the source for the information in this layer. This should be the URL only."> </span>
                    <input type="text" class="mb-4 w3-white form-control" id="source_url" />

                    Linkback
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="The URL linking to the website for your project. This should be the URL only."> </span>
                    <input type="text" class="mb-4 w3-white form-control" id="linkback" />

                    Language
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="The language this layer is in. Use the two-digit language code where possible, such as ‘EN’ for English."></span>
                    <input type="text" class=" mb-2 w3-white form-control" id="language" />

                </div>
                <div class="col-lg p-5">
                    Spatial Coverage
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="The latitude and longitude of the ‘bounding box’ for the area covered by this layer."></span>
                    <div class="border p-3 mb-3">
                        from latitude: <input type="text" class="mb-2 w3-white form-control" id="latitudefrom" />
                        from longitude: <input type="text" class="mb-2 w3-white form-control" id="longitudefrom" />
                        to latitude: <input type="text" class="mb-2 w3-white form-control" id="latitudeto" />
                        to longitude: <input type="text" class="mb-2 w3-white form-control" id="longitudeto" />
                    </div>

                    License
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="The usage licence that applies to this layer. Open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created the information, you can choose the licence. If you obtained it from another source,  the licence specified there."></span>
                    <input type="text" class="mb-2 w3-white form-control" id="license" />

                    Image
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
                    </span>
                    <input class="mb-3" type="file" name="image" id="datasetAddImage" accept="image/*" />

                </div>
                <div class="col-lg p-5">

                    Temporal Coverage
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="The date range covered by the information in this layer."></span>
                    <div class="border p-3 mb-3">
                        <div class="input-group date" id="temporalfromdiv">
                            From: <input type="text" class="mb-2 w3-white form-control input-group-addon" id="temporalfrom" autocomplete="off" />
                        </div>
                        <div class="input-group date" id="temporaltodiv">
                            To: <input type="text" class="mb-2 w3-white form-control input-group-addon"  id="temporalto" autocomplete="off">
                        </div>
                    </div>

                    Date Created
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="The date that the information in this layer was created."></span>
                    <input type="date" class="mb-2 w3-white form-control" id="created" />

                </div>
            </div>


            <div class="row">
                <div class="col-lg p-5">
                    <div class="mb-4">
                        Citation <!-- help hover button -->
                        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="A bibliographic citation people should use when referencing this data, such as its source or related project."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" id="citation"></textarea>
                    </div>

                    <div class="mb-4">
                        Usage Rights
                        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="If not covered by the licence, the rights that apply to use of the information in this layer. You may need to declare that you use it with permission, and others would also have to ask before re-using it; or that it is out of copyright."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" id="rights"></textarea>
                    </div>
                </div>
                <div class="col-lg p-5">
                    <div class="mb-4">
                        Content Warning
                        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                            title="Anything the viewer should be aware of before viewing information in this layer, such as that the content may distress some viewers."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" id="warning"></textarea>
                    </div>

                </div>
            </div>

            <button class="m-4 p-4 btn btn-primary" type="Submit" id="contributesavebtn">Create Layer</button>
        </div>
    </div>

    <div id="loadingWheel-contribute" class="loadingWheel">
        <div class="spinner"></div>
    </div>

@endsection