<!-- MODAL popup -->
<div class="modal fade" id="editDataitemModal" tabindex="-1" role="dialog" aria-labelledby="editDataitemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="editDataitemModalLabel">
                Edit Place
                @include('templates.misc.contentdisclaimer')
            </h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body scrollable">
            <div class="scrollable">
                <label for="editTitle">Title</label><label class="text-danger">*</label>
                <input type="text" class="mb-3 form-control" id="editTitle" placeholder="Title" required>

                <label for="editPlacename">Placename</label>
                <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                      title="Every item must have a Title and Placename is optional. If the purpose is to name a place, then put the Placename in the Title too.">
                    </span>
                <input type="text" class="mb-3 form-control" id="editPlacename" placeholder="Placename" required>

                <label for="editLatitude">Latitude</label><label class="text-danger">*</label>
                <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                      title="Try <a href='https://tlcmap.org/quicktools/quickcoordinates.html'>Quick Coordinates</a>.">
                    </span>
                <input type="text" class="mb-3 form-control" id="editLatitude" placeholder="Latitude" required>

                <label for="editLongitude">Longitude</label><label class="text-danger">*</label>
                <input type="text" class="mb-3 form-control" id="editLongitude" placeholder="Longitude" required>

                <label for="editRecordtype">Record Type</label>
                <select class="w3-white form-control mb-3" id="editRecordtype" name="addrecordtype">
                    @foreach($recordtypes as $type)
                        <option value="{{$type}}">{{$type}}</option>
                    @endforeach
                </select>

                <label for="editDescription">Description</label>
                <!-- script for wysiwyg with tinymce is referenced in userviewdataset.blade.php. Add 'wysiwyger' class to apply it. See wysiwyger.js. Removed for now till we can work on it properly. -->
                <textarea rows="3" class="mb-3 form-control w-100 " style="resize: none" id="editDescription" placeholder="Description"></textarea>

                <label for="editFeatureterm">Feature Term
                    <a href="/guides/featureterms.php" target="_blank">
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="Click here for information on valid feature terms">
                            </span>
                    </a>
                </label>
                <input type="text" class="mb-3 form-control" id="editFeatureterm" placeholder="Feature Term">

                <label for="editState">State</label>
                <select class="w3-white form-control mb-3" name="editState" id="editState">
                    <option value="" selected></option>
                    @foreach($states as $state)
                        <option value="{{$state}}">{{$state}}</option>
                    @endforeach
                </select>

                <div class="input-group date" id="editDateStartDiv">
                    <label for="editDatestart">Date Start</label><input type="text" class="mb-3 form-control input-group-addon" id="editDatestart" autocomplete="off"/>
                </div>
                <div class="input-group date" id="editDateEndDiv">
                    <label for="editDateend">Date End</label><input type="text" class="mb-3 form-control input-group-addon" id="editDateend" autocomplete="off">
                </div>

                <label for="editLga">LGA
                    <a href="/guides/lgas.php" target="_blank">
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                  title="Click here for information on valid Local Government Areas">
                            </span>
                    </a>
                </label>
                <input type="text" class="mb-3 form-control" id="editLga" placeholder="LGA">

                <!-- Linkback with validator-->
                <label for="editExternalurl">Linkback (URL)</label>
                <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                      title="For the URL to be a clickable link please ensure it starts with http:// or https://"></span>
                <input type="text" class="mb-3 form-control" id="editExternalurl" placeholder="Linkback">


                <label for="editSource">Source (Website url, ISBN, Book title, etc)</label>
                <input type="text" class="mb-3 form-control"id="editSource" placeholder="Source"> <!-- TODO: Source could be separate table -->

                <!-- Extended data editor -->
                @include('editors.extended_data_editor')
            </div>
        </div>
        <div class="modal-footer">
            <span class="text-danger">* required fields</span>
            <button type="button" class="btn btn-primary" id="editDataitemSaveButton">Save</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>
