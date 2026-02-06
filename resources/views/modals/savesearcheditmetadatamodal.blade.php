@push('scripts')

<script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
<link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
<script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ asset('js/message-banner.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>

<script>
    var ajaxeditsearch = "{{url('ajaxeditsearch')}}";
</script>

@endpush


<div class="modal fade" id="savesearcheditmetadatamodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit search</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="message-banner"></div>
                <div>
                   <input type="hidden" id="searchID" value="">

                    Search name<label class="text-danger">*</label>
                    <input type="text" id="editName" class="smallerinputs w3-white form-control" />

                    <br>Description<label class="text-danger">*</label>
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="A short paragraph summarising the search. Anything not covered by other fields can be added here."></span>
                    <textarea id="editDescription" rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor"></textarea>

                    <br>Search Type
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="The type of information in this search. If the type is mixed, use ‘other’."></span>
                    <select class="w3-white form-control mb-3" id="editSearchType">
                        @foreach($recordtypes as $type)
                        <option value="{{$type}}">{{$type}}</option>
                        @endforeach
                    </select>

                    <br>Subject (keywords)
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Type and press enter to create keywords describing this search."></span>
                    <input id="editSearchTags" name="tags" type="text" class="smallerinputs mb-2 w3-white form-control" style="height: 50px;" />

                    <br>Content Warning
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Anything the viewer should be aware of before viewing information in this search, such as that the content may distress some viewers."></span>
                    <textarea id="editSearchWarning" rows="3" maxlength="1500" class="w-100 mb-2 w3-white form-control wysiwyg-editor"></textarea>

                    <br>Spatial Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="The latitude and longitude of the ‘bounding box’ for the area covered by this layer."></span>
                    <div class="border p-3 mb-3">
                        from latitude: <input type="text" class="mb-2 w3-white form-control" id="editSearchLatitudeFrom" />
                        from longitude: <input type="text" class="mb-2 w3-white form-control" id="editSearchLongitudeFrom" />
                        to latitude: <input type="text" class="mb-2 w3-white form-control" id="editSearchLatitudeTo" />
                        to longitude: <input type="text" class="mb-2 w3-white form-control" id="editSearchLongitudeTo" />
                    </div>

                    <br>Temporal Coverage
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="The date range covered by the information in this layer."></span>
                    <div class="border p-3 mb-3">
                        <div class="input-group date" id="editDateStartDiv">
                            <label for="editSearchTemporalFrom"> From:</label><input type="text" class="mb-3 form-control input-group-addon" id="editSearchTemporalFrom" autocomplete="off" />
                        </div>
                        <div class="input-group date" id="editDateEndDiv">
                            <label for="editSearchTemporalTo">To:</label><input type="text" class="mb-3 form-control input-group-addon" id="editSearchTemporalTo" autocomplete="off">
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="editSaveSearchButton" type="button">Save</button>
                <button type="button" class="btn btn-secondary" id="saveSearchCloseButton" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>