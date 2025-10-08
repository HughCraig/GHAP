@push('scripts')
    <script src="{{ asset('js/addtodatasetmodal.js') }}"></script>
@endpush

@include('modals.addglycerineimagemodal')

<button type="button" class="mt-3 mb-3 btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Add place to layer</button>
<!-- MODAL popup -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="exampleModalLabel">
                Add to layer
                @include('templates.misc.contentdisclaimer')
            </h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body scrollable">
            <div class="scrollable">
                <div class="message-banner pt-4 pb-4 mb-3"></div>

                <label for="addtitle">Title</label><label class="text-danger">*</label>
                <input type="text" class="mb-3 form-control" id="addtitle" placeholder="Title" required>

                <label for="addplacename">Placename</label>
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title="Every item must have a Title and Placename is optional. If the purpose is to name a place, then put the Placename in the Title too.">
                    </span>
                <input type="text" class="mb-3 form-control" id="addplacename" placeholder="Placename" required>

                <!-- Image Upload -->
                <label for="addImage">Image</label> 
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
                </span>
                <input type="file" class="form-control" id="addImage" name="image" accept="image/*">

                <!-- Glycerine Image -->
                <label for="addGlycerineImageButton" class="mt-4">Glycerine Image</label>
                <div id="add-glycerine-url-container" style="display: none;">
                </div>
                <div>
                    <button type="button" class="btn btn-default btn-sm mb-3" id="addGlycerineImageButton">Add Glycerine Image</button>
                </div>

                <div class="map-picker">
                    <p><small>Either enter coordinates manually or click on the map and apply.</small></p>

                    <label for="addlatitude">Latitude</label><label class="text-danger">*</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                          title="Try <a href='https://tlcmap.org/quicktools/quickcoordinates.html'>Quick Coordinates</a>.">
                    </span>
                    <input type="text" class="mb-3 form-control mp-input-lat" id="addlatitude" placeholder="Latitude" required>

                    <label for="addlongitude">Longitude</label><label class="text-danger">*</label>
                    <input type="text" class="mb-3 form-control mp-input-lng" id="addlongitude" placeholder="Longitude" required>

                    <div class="mb-3">
                        <button type="button" class="btn btn-default btn-sm mp-btn-refresh" title="Refresh the map to reflect coordinate changes">Refresh Map</button>
                        <button type="button" class="btn btn-default btn-sm mp-btn-unset" title="Unset the coordinates">Unset</button>
                    </div>

                    <div class="mp-map"></div>
                </div>

                <label for="addrecordtype">Record Type</label>
                <select class="w3-white form-control mb-3" id="addrecordtype" name="addrecordtype">
                    @foreach($recordtypes as $type)
                        <option label="{{$type}}">{{$type}}</option>
                    @endforeach
                </select>

                <div class="mb-3">
                    <label for="adddescription">Description</label>
                    <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="adddescription" placeholder="Description"></textarea>
                </div>

                <label for="addfeatureterm">Feature Term
                    <a href="/guides/featureterms.php" target="_blank">
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                                  title="Click here for information on valid feature terms">
                            </span>
                    </a>
                </label>
                <input type="text" class="mb-3 form-control" id="addfeatureterm" placeholder="Feature Term">

                <label for="addstate">State</label>
                <select class="w3-white form-control mb-3" name="addstate" id="addstate">
                    <option label selected></option>
                    @foreach($states as $state)
                        <option label="{{$state}}">{{$state}}</option>
                    @endforeach
                </select>

                <div class="input-group date" id="addDateStartDiv">
                    <label for="adddatestart">Date Start</label><input type="text" class="mb-3 form-control input-group-addon" id="adddatestart" autocomplete="off"/>
                </div>
                <div class="input-group date" id="addDateEndDiv">
                    <label for="adddateend">Date End</label><input type="text" class="mb-3 form-control input-group-addon" id="adddateend" autocomplete="off">
                </div>

                <label for="addlga">LGA
                    <a href="/guides/lgas.php" target="_blank">
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                                  title="Click here for information on valid Local Government Areas">
                            </span>
                    </a>
                </label>
                <input type="text" class="mb-3 form-control" id="addlga" placeholder="LGA">

                <!-- Linkback with validator-->
                <label for="addexternalurl">Linkback (URL)</label>
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title="For the URL to be a clickable link please ensure it starts with http:// or https://"></span>
                <input type="text" class="mb-3 form-control" id="addexternalurl" placeholder="Linkback">

                <div class="mb-3">
                    <label for="addsource">Source (Website url, ISBN, Book title, etc)</label>
                    <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="addsource" placeholder="Source"></textarea>
                </div>

                <!-- Extended data editor -->
                @include('editors.extended_data_editor')

            </div>
        </div>
        <div class="modal-footer">
            <span class="text-danger">* required fields</span>
            <button type="button" class="btn btn-primary" id="add_dataitem_button_submit">Add Place</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>
