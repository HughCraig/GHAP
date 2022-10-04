@push('scripts')
    <script src="{{ asset('js/addtodatasetmodal.js') }}"></script>
@endpush

<button type="button" class="mt-3 mb-3 btn btn-primary" data-toggle="modal" data-target="#addModal">Add to layer</button>
<!-- MODAL popup -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="exampleModalLabel">
                Add to layer
                @include('templates.misc.contentdisclaimer')
            </h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-lg-6">
                    <label for="addtitle">Title</label><label class="text-danger">*</label>
                    <input type="text" class="mb-3 form-control" id="addtitle" placeholder="Title" required>

                    <label for="addplacename">Placename</label>
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                          title="Every item must have a Title and Placename is optional. If the purpose is to name a place, then put the Placename in the Title too.">
                    </span>
                    <input type="text" class="mb-3 form-control" id="addplacename" placeholder="Placename" required>

                    <label for="addlatitude">Latitude</label><label class="text-danger">*</label>
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                          title="Try <a href='https://tlcmap.org/quicktools/quickcoordinates.html'>Quick Coordinates</a>.">
                    </span>
                    <input type="text" class="mb-3 form-control" id="addlatitude" placeholder="Latitude" required>

                    <label for="addlongitude">Longitude</label><label class="text-danger">*</label>
                    <input type="text" class="mb-3 form-control"id="addlongitude" placeholder="Longitude" required>

                    <label for="addrecordtype">Record Type</label>
                    <select class="w3-white form-control" id="addrecordtype" name="addrecordtype">
                        @foreach($recordtypes as $type)
                            <option label="{{$type}}">{{$type}}</option>
                        @endforeach
                    </select>

                    <label for="adddescription">Description</label>
                    <!-- script for wysiwyg with tinymce is referenced in userviewdataset.blade.php. Add 'wysiwyger' class to apply it. See wysiwyger.js. Removed for now till we can work on it properly. -->
                    <textarea rows="3" class="mb-3 form-control w-100 " style="resize: none" id="adddescription" placeholder="Description"></textarea>

                    <label for="addfeatureterm">Feature Term
                        <a href="/guides/featureterms.php" target="_blank">
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="Click here for information on valid feature terms">
                            </span>
                        </a>
                    </label>
                    <input type="text" class="mb-3 form-control" id="addfeatureterm" placeholder="Feature Term">

                    <label for="addstate">State</label>
                    <select class="w3-white form-control" name="addstate" id="addstate">
                        @foreach($states as $state)
                        <option label="{{$state->state_code}}">{{$state->state_code}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-6">
                    <div class="input-group date" id="addDateStartDiv">
                        <label for="adddatestart">Date Start</label><input type="text" class="mb-3 form-control input-group-addon" id="adddatestart" autocomplete="off"/>
                    </div>
                    <div class="input-group date" id="addDateEndDiv">
                        <label for="adddateend">Date End</label><input type="text" class="mb-3 form-control input-group-addon" id="adddateend" autocomplete="off">
                    </div>

                    <label for="addlga">LGA
                        <a href="/guides/lgas.php" target="_blank">
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="Click here for information on valid Local Government Areas">
                            </span>
                        </a>
                    </label>
                    <input type="text" class="mb-3 form-control" id="addlga" placeholder="LGA">

                    <!-- Linkback with validator-->
                    <label for="addexternalurl">Linkback (URL)</label>
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="For the URL to be a clickable link please ensure it starts with http:// or https://"></span>
                    <input type="text" class="mb-3 form-control" id="addexternalurl" placeholder="Linkback">


                    <label for="addsource">Source (Website url, ISBN, Book title, etc)</label>
                    <input type="text" class="mb-3 form-control"id="addsource" placeholder="Source"> <!-- TODO: Source could be separate table -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <span class="text-danger">* required fields</span>
            <button type="button" class="btn btn-primary" id="add_dataitem_button_submit">Add Item</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>
