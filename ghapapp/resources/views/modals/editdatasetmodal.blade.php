<script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
<link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">

<link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
<script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>

<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editdatasetModal">Edit Layer</button>
<!-- MODAL popup -->
<div class="modal fade" id="editdatasetModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">Edit Layer</h3>
                
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form method="POST" id="edit_dataset_form" action="{{url()->full()}}/edit">
            <div class="modal-body">
                @csrf
                @include('templates.misc.contentdisclaimer')
                <div class="row">
                    <div class="col-lg-6">
                        Layer name<label class="text-danger">*</label><input type="text" class="mb-4 w3-white form-control" name="dsn" value="{{$ds->name}}" required />
                        Subject (keywords) 
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="Type and press enter to create keywords describing this layer."></span>
                        <input id="tags" name="tags" type="text" class="smallerinputs mb-4 w3-white form-control"  />
                        
                        
                        Description<label class="text-danger">*</label>
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="A short paragraph summarising this information and its context. Anything not covered by other fields can be added here."></span>
                        <textarea rows="3" maxlength="500" class="w-100 mb-4 w3-white form-control" name="description" id="description" required >{{$ds->description}}</textarea>

Record Type 
<span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="A general indication of the type of information in this layer. Eg: is it list of placenames, a journey, or biographical information about a person? 
                        If it is mixed, use 'other'. This may be used to refine searches and display icons on maps of search results."></span>
<select class="mb-4 w3-white form-control" id="recordtype" name="recordtype" value="{{$ds->recordtype->type}}" oldvalue="{{$ds->recordtype->type}}">>
@foreach($recordtypes as $type)
	@if($type == $ds->recordtype->type) <option label="{{$type}}" selected>{{$type}}</option>
	@else <option label="{{$type}}">{{$type}}</option> @endif
@endforeach
</select>

Content Warning 
<span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="Anything the viewer should be aware of before viewing information in this layer. TLCMap will attempt to show this information before or while it is shown.
                        Eg: warn that the data was harvested by computer and is not human checked, or that the content may distress some viewers, etc."></span>
			
                        <textarea rows="3" maxlength="500" class="w-100 mb-4 w3-white form-control" name="warning" id="warning"  >{{$ds->warning}}</textarea>




			
                        Visibility
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="TLCMap is intended for making information public, but you can set it to private while you work on it if you want. Some visualisations may not work while set to private."></span>
                        <select id="public" name="public" class="mb-4 w3-white form-control"><option value="0">Private</option><option value="1">Public</option></select>
                        <script>document.getElementById("public").selectedIndex = {{($ds->public) ? '1' : '0' }}</script>
                        Allow ANPS to collect this data? 
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="The TLCMap Gazetteer was based on information collected and compiled by Australian National Placenames Survey, who keep records on historical and other placenames, 
                        not just the official ones. If your information includes placenames, we'd like to provide them back to them to help their research and record keeping."></span>
                        <select id="allowanps" name="allowanps" class="mb-4 w3-white form-control"><option value="0">No</option><option value="1">Yes</option></select>
                        <script>document.getElementById("allowanps").selectedIndex = {{($ds->allowanps) ? '1' : '0' }}</script>
                        Creator 
            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="This could be the person or organisation who researched or prepared the data and/or you because you are uploading it.
                        As there are many possibilities for who should be credited as creator/s in different situations - you can decide was is appropriate."></span>
                        <input type="text" class="mb-4 w3-white form-control" name="creator" value="{{$ds->creator}}"  />
                        Publisher<input type="text" class="mb-4 w3-white form-control" name="publisher" value="{{$ds->publisher}}" />
                        Contact 
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="For if people have questions, comments about this information."></span>
                        <input type="text" class="mb-4 w3-white form-control" name="contact" value="{{$ds->contact}}" />



                        Language 
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="What language is this layer in? Use the two digit language code where possible. Eg: 'EN' for English."></span>
                        <input type="text" class=" mb-4 w3-white form-control" name="language" value="{{$ds->language}}" />
                        License
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="What usage licence applies to this. Eg: open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created this information you can decide the licence. 
                        If you obtained from another source and are using under that licence, you can put that licence."></span>

			<input type="text" class=" mb-4 w3-white form-control" name="license" value="{{$ds->license}}" />
			Rights 
            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="If not covered by the licence, what are the rights that apply to use of this information.
                        Eg: you may need to declare that you use it with permission, and others would also have to ask before re-using it, or that it is out of copyright, etc."></span>
                        <input type="text" class="mb-4 w3-white form-control" name="rights" value="{{$ds->rights}}" />
                       
                    </div>
                    <div class="col-lg-6">
                        Citation 
                        
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="A bibliographic style citation people should use when referencing this data, such as its source or related project."></span>
                        <input type="text" class="mb-4 w3-white form-control" name="citation" value="{{$ds->citation}}" />

                        DOI 
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="A valid DOI (Data Object Identifier) if you have one for the 'official' version of the information in this layer. TLCMap can be a seen as way to discover and visualise the 
                        information, but isn't an official research archive or data repository. You can always get a DOI later if you want.
                        This TLCMap layer will also recieve a unique identifier and URL that can be used in citations, though it is not a DOI."></span>
                        <input type="text" class="mb-4 w3-white form-control" name="doi" value="{{$ds->doi}}" />
                       
                        Source URL <!-- help hover button -->
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="The URL linking to the website that hosts this information, its origin, the archive, public entry point, or otherwise. This should be the URL only so it can be linked."> </span>
                        <input type="text" class="mb-4 w3-white form-control" name="source_url" value="{{$ds->source_url}}" />

                        Spatial Coverage 
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="The latitude and longitude of the 'bounding box' including all points."></span>
                        <div class="border p-3 mb-4">
                            from latitude: <input type="text" class="mb-4 w3-white form-control" name="latitudefrom" value="{{$ds->latitude_from}}" />
                            from longitude: <input type="text" class="mb-4 w3-white form-control" name="longitudefrom" value="{{$ds->longitude_from}}" />
                            to latitude: <input type="text" class="mb-4 w3-white form-control" name="latitudeto" value="{{$ds->latitude_to}}" />
                            to longitude: <input type="text" class="mb-4 w3-white form-control" name="longitudeto" value="{{$ds->longitude_to}}" />
                        </div>
                        Temporal Coverage
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="What usage licence applies to this. Eg: open data is often under a <a href='https://creativecommons.org/licenses/' target='_blank'>Creative Commons</a> CC BY or CC BY-NC licence. If you created this information you can decide the licence. 
                        If you obtained from another source and are using under that licence, you can put that licence."></span>

                        <div class="border p-3 mb-4">
                        <div class="input-group date" id="temporalfromdiv">
                            From: <input type="text" value="{{$ds->temporal_from}}" class="mb-2 w3-white form-control input-group-addon" name="temporalfrom" id="temporalfrom" autocomplete="off"/>
                        </div>
                        <div class="input-group date" id="temporaltodiv">
                            To: <input type="text" value="{{$ds->temporal_to}}" class="mb-2 w3-white form-control input-group-addon" name="temporalto" id="temporalto" autocomplete="off">
                        </div>
                        <script type="text/javascript">
                            $(function () {
                                $('#temporalfromdiv').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
                                $('#temporaltodiv').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
                            });
                        </script>
                        </div>  
                        Date Created 
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                        title="When was the information information in this layer collected/researched/created?"></span>
                        <input type="date" class="mb-4 w3-white form-control" name="created" value="{{$ds->created}}" />
                        * Accept <a href="/cou/" target="_blank">Conditions of use</a> <input type="checkbox" id="cou" name="cou" required></input>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <span class="text-danger">* required fields</span>
                <input class="btn btn-primary" type="submit" value="Save" name="Save">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
            </form>                  
        </div>
    </div>
</div>

<script src="{{ asset('/js/form.js') }}"></script> <!-- So we can quickly reuse the date regex check code -->

<script>
        //Initiate jQuery tagsInput function AND Adjust the settings for the tags field
        $('#tags').tagsInput({
            'height':'50px',
            'width':'100%',
            'interactive':true,
            'defaultText':'add a tag',
            'delimiter': [',',';'],   // Or a string with a single delimiter. Ex: ';'
            'removeWithBackspace' : true,
            'minChars' : 0,
            'maxChars' : 0, // if not provided there is no limit
            'placeholderColor' : '#666666',
            'overflow' : 'auto'
        });

        //Make it look like the other inputs
        $('#tags_tagsinput').addClass('form-control').addClass('mb-2')

        //prefill the form with the keywords associated with this dataset
        $.each({!! $ds->subjectkeywords !!}, function(index, value) {
            $('#tags').addTag(value.keyword);
        });
    </script>
