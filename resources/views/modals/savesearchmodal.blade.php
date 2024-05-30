@push('styles')
<link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>

<script>
    var ajaxsavesearch = "{{url('ajaxsavesearch')}}";
</script>

<script src="{{ asset('/js/savesearchmodal.js') }}"></script>
@endpush


<button id="saveSearchModalButton" type="button" class="mt-3 mb-3 btn btn-primary" data-toggle="modal" data-target="#saveSearchModal">Save your search</button>
<!-- MODAL popup -->
<div class="modal fade" id="saveSearchModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">Save your search</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="message-banner"></div>
                <div>

                    <input type="hidden" id="save_search_count"/>

                    Search name<label class="text-danger">*</label>
                    <input type="text" class="smallerinputs w3-white form-control" id="save_search_name" />

                    <br>Description<label class="text-danger">*</label>
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="A short paragraph summarising the search. Anything not covered by other fields can be added here."></span>
                    <textarea id="save_search_description" rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor"></textarea>

                    <br>Search Type
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="The type of information in this search. If the type is mixed, use ‘other’."></span>
                    <select class="w3-white form-control mb-2" id="save_search_recordtype">
                        @foreach($recordtypes as $type)
                        <option label="{{$type->type}}">{{$type->type}}</option>
                        @endforeach
                    </select>

                    <br>Subject (keywords)
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Type and press enter to create keywords describing this search."></span>
                    <input id="save_search_tags" name="tags" type="text" class="smallerinputs mb-2 w3-white form-control" style="height: 50px;" />

                    <br>Content Warning
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Anything the viewer should be aware of before viewing information in this search, such as that the content may distress some viewers."></span>
                    <textarea id="save_search_warning" rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor"></textarea>

                    <br>Spatial Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="The latitude and longitude of the ‘bounding box’ for the area covered by this layer."></span>
                    <div class="border p-3 mb-3">
                        from latitude: <input type="text" class="mb-2 w3-white form-control" id="save_search_latitudefrom" />
                        from longitude: <input type="text" class="mb-2 w3-white form-control" id="save_search_longitudefrom" />
                        to latitude: <input type="text" class="mb-2 w3-white form-control" id="save_search_latitudeto" />
                        to longitude: <input type="text" class="mb-2 w3-white form-control" id="save_search_longitudeto" />
                    </div>

                    <br>Temporal Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="The date range covered by the information in this layer."></span>
                    <div class="border p-3 mb-3">
                        <div class="input-group date" id="temporalfromdiv">
                            From: <input type="text" class="mb-2 w3-white form-control input-group-addon" id="save_search_temporalfrom" autocomplete="off" />
                        </div>
                        <div class="input-group date" id="temporaltodiv">
                            To: <input type="text" class="mb-2 w3-white form-control input-group-addon" id="save_search_temporalto" autocomplete="off">
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="save_search_button" type="button">Save your search</button>
                <button type="button" class="btn btn-secondary" id="saveSearchCloseButton" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>