@extends('templates.layout')

@push('styles')
    <link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
    <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
    <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/usernewdataset.js') }}"></script>
    <script src="{{ asset('/js/dataitem.js') }}"></script> <!-- So we can quickly reuse the URL validation code -->
    <script src="{{ asset('/js/form.js') }}"></script> <!-- So we can quickly reuse the date regex check code -->
@endpush

@section('content')

    <h2>{{ Auth::user()->name }}'s New Layer</h2>
    @include('templates.misc.contentdisclaimer')
    <p class="h4">See the <a href="https://www.tlcmap.org/guides/ghap/#contribute" target="_blank">Guide</a> for help and instructions on creating and adding to layers.</p>
    <div class="container-fluid border">
        <form method="POST" id="new_dataset_form" action="{{url()->full()}}/create">
            @csrf
            <div class="row">
                <div class="col-lg p-5">
                    *Layer name
                    <input type="text" class="mb-2 w3-white form-control" name="dsn" required />

                    Subject (keywords) 
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="Type and press enter to create keywords describing this layer."></span>
                        <input id="tags" name="tags" type="text" class="smallerinputs mb-2 w3-white form-control" />

                    *Description
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="A short paragraph summarising this information and its context. Anything not covered by other fields can be added here."></span>
                    <textarea rows="3" maxlength="500" class="w-100 mb-2 w3-white form-control" name="description" required ></textarea>

                    Record Type
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                            title="A general indication of the type of information in this layer. Eg: is it list of placenames, a journey, or biographical information about a person?
                                            If it is mixed, use 'other'. This may be used to refine searches and display icons on maps of search results."></span>
                    <select class="w3-white form-control" id="recordtype" name="recordtype">
                        @foreach($recordtypes as $type)
                            <option label="{{$type}}">{{$type}}</option>
                        @endforeach
                    </select>

                    Content Warning 
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="Anything the viewer should be aware of before viewing information in this layer. TLCMap will attempt to show this information before or while it is shown.
                        Eg: warn that the data was harvested by computer and is not human checked, or that the content may distress some viewers, etc."></span>
                        <textarea rows="3" maxlength="500" class="w-100 mb-2 w3-white form-control" name="warning" ></textarea>

                    Visibility 
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="TLCMap is intended for making information public, but you can set it to private while you work on it if you want. Some visualisations may not work while set to private."></span>
                    <select id="public" name="public" class="mb-4 w3-white form-control"><option value="0">Private</option><option value="1" selected="selected">Public</option></select>

                    Allow ANPS to collect this data?
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The TLCMap Gazetteer was based on information collected and compiled by Australian National Placenames Survey, who keep records on historical and other placenames, 
                        not just the official ones. If your information includes placenames, we'd like to provide them back to them to help their research and record keeping."></span> <select id="allowanps" name = "allowanps" class="mb-4 w3-white form-control"><option value="0">No</option><option value="1">Yes</option></select>
                </div>
                
                <div class="col-lg p-5">
                    Creator
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="This could be the person or organisation who researched or prepared the data and/or you because you are uploading it.
                        As there are many possibilities for who should be credited as creator/s in different situations - you can decide was is appropriate."></span>
                        <input type="text" class="mb-2 w3-white form-control" name="creator"/>
                    
                    Publisher
                    <input type="text" class="mb-2 w3-white form-control" name="publisher"/>

                    Contact
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="For if people have questions, comments about this information."></span>
                        <input type="text" class="mb-2 w3-white form-control" name="contact"/>

                    Citation <!-- help hover button -->
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="A bibliographic style citation people should use when referencing this data, such as its source or related project."></span>
                    <input type="text" class="mb-4 w3-white form-control" name="citation"/>

                    DOI <!-- help hover button -->
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="A valid DOI (Data Object Identifier) if you have one for the 'official' version of the information in this layer. TLCMap can be a seen as way to discover and visualise the 
                        information, but isn't an official research archive or data repository. You can always get a DOI later if you want.
                        This TLCMap layer will also recieve a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                    <input type="text" class="mb-4 w3-white form-control" name="doi"/>

                    Source URL <!-- help hover button -->
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="The URL linking to the website that hosts this information, its origin, the archive, public entry point, or otherwise. This should be the URL only so it can be linked."> </span>
                    <input type="text" class="mb-4 w3-white form-control" name="source_url"/>

                    Language
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="What language is this layer in? Use the two digit language code where possible. Eg: 'EN' for English."></span>
                        <input type="text" class=" mb-2 w3-white form-control" name="language"/> 
                </div>
                <div class="col-lg p-5">
                    Spatial Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="The latitude and longitude of the 'bounding box' including all points."></span>
                    <div class="border p-3 mb-3">
                        from latitude: <input type="text" class="mb-2 w3-white form-control" name="latitudefrom"/>
                        from longitude: <input type="text" class="mb-2 w3-white form-control" name="longitudefrom"/>
                        to latitude: <input type="text" class="mb-2 w3-white form-control" name="latitudeto"/>
                        to longitude: <input type="text" class="mb-2 w3-white form-control" name="longitudeto"/>
                    </div>

                    License
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="What usage licence applies to this. Eg: open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created this information you can decide the licence. 
                        If you obtained from another source and are using under that licence, you can put that licence."></span>
                        <input type="text" class="mb-2 w3-white form-control" name="license"/>

                    Usage Rights 
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="If not covered by the licence, what are the rights that apply to use of this information.
                        Eg: you may need to declare that you use it with permission, and others would also have to ask before re-using it, or that it is out of copyright, etc."></span>
                        <input type="text" class="mb-2 w3-white form-control" name="rights"/>  
                </div>
                <div class="col-lg p-5">
                    Temporal Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="What is the first and last date date of this information?"></span>
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
                        title="When was the information information in this layer collected/researched/created?"></span>
                        <input type="date" class="mb-2 w3-white form-control" name="created"/>
                </div>
            </div>
            * Accept <a href="/cou/" target="_blank"> Conditions of use</a> <input type="checkbox" id="cou" name="cou" required></input>

            <button class="m-4 p-4 btn btn-primary" type="Submit">Create Layer</button>
        </form>
    </div>
    <div class="mt-4 m-0 row"><a href="{{url('myprofile/mydatasets')}}" class="mb-3 btn btn-primary">Back</a></div>

@endsection
