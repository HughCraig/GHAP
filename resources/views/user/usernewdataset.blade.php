@extends('templates.layout')

@push('styles')
    <link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
    <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
    <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
    <script> 
        const max_upload_image_size = {{ config('app.max_upload_image_size') }};
    </script>
    <script src="{{ asset('js/usernewdataset.js') }}"></script>
    <script src="{{ asset('/js/dataitem.js') }}"></script> <!-- So we can quickly reuse the URL validation code -->
    <script src="{{ asset('/js/form.js') }}"></script> <!-- So we can quickly reuse the date regex check code -->
@endpush

@section('content')

    <h2>
        {{ Auth::user()->name }}'s New Layer
        @include('templates.misc.contentdisclaimer')
    </h2>
    <p class="h4">See the <a href="https://www.tlcmap.org/guides/ghap/#contribute" target="_blank">Guide</a> for help and instructions on creating and adding to layers.</p>
    <div class="container-fluid border">
        <form method="POST" id="new_dataset_form" action="{{url()->full()}}/create" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-lg p-5">
                    *Layer name
                    <input type="text" class="mb-2 w3-white form-control" name="dsn" required />

                    Subject (keywords) 
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="Type and press enter to create keywords describing this layer."></span>
                        <input id="tags" name="tags" type="text" class="smallerinputs mb-2 w3-white form-control" />

                    Record Type
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                            title="The type of information in this layer. If the type is mixed, use ‘other’."></span>
                    <select class="w3-white form-control mb-2" id="recordtype" name="recordtype">
                        @foreach($recordtypes as $type)
                            <option label="{{$type}}">{{$type}}</option>
                        @endforeach
                    </select>

                    Visibility 
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="TLCMap is intended for making information public, but you can set it to private while you work on it if you want. Some visualisations may not work while the layer is set to private."></span>
                    <select id="public" name="public" class="mb-4 w3-white form-control"><option value="0">Private</option><option value="1" selected="selected">Public</option></select>

                    Allow ANPS to collect this data?
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="GHAP is based on information collected and compiled by the Australian National Placenames Survey, who keep records of historical and other placenames. If your layer includes placenames, we’d like to provide them back to ANPS to help their research and records."></span>
                    <select id="allowanps" name = "allowanps" class="mb-4 w3-white form-control"><option value="0">No</option><option value="1">Yes</option></select>

                    Creator
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                          title="The person or organisation who researched or prepared the data."></span>
                    <input type="text" class="mb-2 w3-white form-control" name="creator"/>
                </div>
                
                <div class="col-lg p-5">
                    Publisher
                    <input type="text" class="mb-2 w3-white form-control" name="publisher"/>

                    Contact
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="Contact details if people have questions about this layer."></span>
                        <input type="text" class="mb-2 w3-white form-control" name="contact"/>



                    DOI <!-- help hover button -->
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="A valid Data Object Identifier for the ‘official’ version of the information in this layer. TLCMap can be used to visualise the information, but isn’t an official research archive or data repository. You can always add a DOI later. This layer will also receive a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                    <input type="text" class="mb-4 w3-white form-control" name="doi"/>

                    Source URL <!-- help hover button -->
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="The URL linking to the source for the information in this layer. This should be the URL only."> </span>
                    <input type="text" class="mb-4 w3-white form-control" name="source_url"/>

                    Linkback
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                          title="The URL linking to the website for your project. This should be the URL only."> </span>
                    <input type="text" class="mb-4 w3-white form-control" name="linkback"/>

                    Language
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The language this layer is in. Use the two-digit language code where possible, such as ‘EN’ for English."></span>
                        <input type="text" class=" mb-2 w3-white form-control" name="language"/> 
                </div>
                <div class="col-lg p-5">
                    Spatial Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The latitude and longitude of the ‘bounding box’ for the area covered by this layer."></span>
                    <div class="border p-3 mb-3">
                        from latitude: <input type="text" class="mb-2 w3-white form-control" name="latitudefrom"/>
                        from longitude: <input type="text" class="mb-2 w3-white form-control" name="longitudefrom"/>
                        to latitude: <input type="text" class="mb-2 w3-white form-control" name="latitudeto"/>
                        to longitude: <input type="text" class="mb-2 w3-white form-control" name="longitudeto"/>
                    </div>

                    License
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The usage licence that applies to this layer. Open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created the information, you can choose the licence. If you obtained it from another source, select the licence specified there."></span>
                        <input type="text" class="mb-2 w3-white form-control" name="license"/>

                    Image
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                      title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
                    </span>
                    <input type="file" name="image" id="datasetAddImage" accept="image/*"/>
                </div>
                <div class="col-lg p-5">
                    Temporal Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The date range covered by the information in this layer."></span>
                    <div class="border p-3 mb-3">
                        <div class="input-group date" id="temporalfromdiv">
                            From: <input type="text" class="mb-2 w3-white form-control input-group-addon" name="temporalfrom" id="temporalfrom" autocomplete="off"/>
                        </div>
                        <div class="input-group date" id="temporaltodiv">
                            To: <input type="text" class="mb-2 w3-white form-control input-group-addon" name="temporalto" id="temporalto" autocomplete="off">
                        </div>
                    </div>

                    Date Created 
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="The date that the information in this layer was created."></span>
                        <input type="date" class="mb-2 w3-white form-control" name="created"/>
                </div>
            </div>
            <div class="row">
                <div class="col-lg p-5">
                    <div class="mb-4">
                        *Description
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                              title="A short paragraph summarising the layer. Anything not covered by other fields can be added here."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" name="description"></textarea>
                    </div>

                    <div class="mb-4">
                        Citation <!-- help hover button -->
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                              title="A bibliographic citation people should use when referencing this data, such as its source or related project."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" name="citation"></textarea>
                    </div>
                </div>
                <div class="col-lg p-5">
                    <div class="mb-4">
                        Content Warning
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                              title="Anything the viewer should be aware of before viewing information in this layer, such as that the content may distress some viewers."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" name="warning" ></textarea>
                    </div>
                    <div class="mb-4">
                        Usage Rights
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                              title="If not covered by the licence, the rights that apply to use of the information in this layer. You may need to declare that you use it with permission, and others would also have to ask before re-using it; or that it is out of copyright."></span>
                        <textarea rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor" name="rights" ></textarea>
                    </div>
                </div>
            </div>

            <button class="m-4 p-4 btn btn-primary" type="Submit" id="addDatasetSaveButton">Create Layer</button>
        </form>
    </div>
    <div class="mt-4 m-0 row"><a href="{{url('myprofile/mydatasets')}}" class="mb-3 btn btn-primary">Back</a></div>

@endsection
