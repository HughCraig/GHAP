@push('scripts')
    <script>
        var updateCurrRouteDetailsUrl = "{{ url('dataitems/{dataitemId}/current-routes-details') }}";
        var updateOtherRoutesDetailsUrl = "{{ url('dataitems/{dataitemId}/other-routes-details') }}";
    </script>
@endpush
<!-- MODAL popup -->
<div class="modal fade" id="editDataitemModal" tabindex="-1" role="dialog" aria-labelledby="editDataitemModalLabel"
    aria-hidden="true">
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
                    <div class="message-banner pt-4 pb-4 mb-3"></div>

                    <label for="editTitle">Title</label><label class="text-danger">*</label>
                    <input type="text" class="mb-3 form-control" id="editTitle" placeholder="Title" required>

                    <label for="editPlacename">Placename</label>
                    <span tabindex="0" data-html="true" data-animation="true"
                        class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="Every item must have a Title and Placename is optional. If the purpose is to name a place, then put the Placename in the Title too.">
                    </span>
                    <input type="text" class="mb-3 form-control" id="editPlacename" placeholder="Placename" required>

                    <!-- Image Upload -->
                    <label for="editImage">Image</label>
                    <span tabindex="0" data-html="true" data-animation="true"
                        class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title='Max upload size {{ floor(config('app.max_upload_image_size') / (1024 * 1024)) . ' MB' }}'>
                    </span>
                    <div id="editImageContainer" class="mb-3" style="display: none;">
                        <img id="editImagePreview" src="#" alt="Place Image" style="max-height: 150px;">
                    </div>
                    <input type="file" class="form-control" id="editImage" name="image" accept="image/*">

                    <div class="map-picker">
                        <p><small>Either enter coordinates manually or click on the map and apply.</small></p>

                        <label for="editLatitude">Latitude</label><label class="text-danger">*</label>
                        <span tabindex="0" data-html="true" data-animation="true"
                            class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                            title="Try <a href='https://tlcmap.org/quicktools/quickcoordinates.html'>Quick Coordinates</a>.">
                        </span>
                        <input type="text" class="mb-3 form-control mp-input-lat" id="editLatitude"
                            placeholder="Latitude" required>

                        <label for="editLongitude">Longitude</label><label class="text-danger">*</label>
                        <input type="text" class="mb-3 form-control mp-input-lng" id="editLongitude"
                            placeholder="Longitude" required>

                        <div class="mb-3">
                            <button type="button" class="btn btn-default btn-sm mp-btn-refresh"
                                title="Refresh the map to reflect coordinate changes">Refresh Map</button>
                            <button type="button" class="btn btn-default btn-sm mp-btn-unset"
                                title="Unset the coordinates">Unset</button>
                        </div>

                        <div class="mp-map"></div>
                    </div>

                    <div class="mb-3">
                        <label for="editDescription">Description</label>
                        <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="editDescription" placeholder="Description"></textarea>
                    </div>

                    <label for="editFeatureterm">Feature Term
                        <a href="/guides/featureterms.php" target="_blank">
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                                title="Click here for information on valid feature terms">
                            </span>
                        </a>
                    </label>
                    <input type="text" class="mb-3 form-control" id="editFeatureterm" placeholder="Feature Term">

                    <label for="editState">State</label>
                    <select class="w3-white form-control mb-3" name="editState" id="editState">
                        <option value="" selected></option>
                        @foreach ($states as $state)
                            <option value="{{ $state }}">{{ $state }}</option>
                        @endforeach
                    </select>

                    <div class="input-group date" id="editDateStartDiv">
                        <label for="editDatestart">Date Start</label><input type="text"
                            class="mb-3 form-control input-group-addon" id="editDatestart" autocomplete="off" />
                    </div>
                    <div class="input-group date" id="editDateEndDiv">
                        <label for="editDateend">Date End</label><input type="text"
                            class="mb-3 form-control input-group-addon" id="editDateend" autocomplete="off">
                    </div>

                    <label for="editLga">LGA
                        <a href="/guides/lgas.php" target="_blank">
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                data-placement="right"
                                title="Click here for information on valid Local Government Areas">
                            </span>
                        </a>
                    </label>
                    <input type="text" class="mb-3 form-control" id="editLga" placeholder="LGA">

                    <!-- Linkback with validator-->
                    <label for="editExternalurl">Linkback (URL)</label>
                    <span tabindex="0" data-html="true" data-animation="true"
                        class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="For the URL to be a clickable link please ensure it starts with http:// or https://"></span>
                    <input type="text" class="mb-3 form-control" id="editExternalurl" placeholder="Linkback">

                    <div class="mb-3">
                        <label for="editSource">Source (Website url, ISBN, Book title, etc)</label>
                        <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="editSource" placeholder="Source"></textarea>
                    </div>
                    <!-- Record Type Editor-->
                    <label for="editRecordtype">Record Type</label>
                    <select class="w3-white form-control mb-3" id="editRecordtype" name="editrecordtype">
                        @foreach ($recordtypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>

                    <!-- Quantity-->
                    <div class="edit-quantity-group" style="display: none;">
                        <label for="editquantity">Quantity</label>
                        <span tabindex="0" data-html="true" data-animation="true"
                            class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                            title="Please ensure you enter an integer greater or equal to 0. <br> Clearing the box will erase the quantity value for this place."></span>
                        <input type="text" class="mb-3 form-control" id="editQuantity"
                            placeholder="Non-negative Integer / Keep it empty">
                        {{-- TODO: Not sure how can I validate the number properliy, use the string validation like coordinates for now. --}}
                        {{-- <input type="number" min="0" step="1" class="mb-3 form-control" id="editquantity" placeholder="10"> --}}
                    </div>

                    <!-- Route Info-->
                    <div class="edit-route-info-group border p-3" style="display: none;">
                        <p><small>Edit the route information.</small></p>
                        <div class="route-options-container"></div>
                        <div class="row route-existing-row" style="display: none;">
                            <div class="col-md-8">
                                <label for="editRouteId">Route ID</label>
                                <select class="form-control" id="editRouteId"><label class="text-danger">*</label>
                                    <option value="">Select Route ID</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="editStopIdx">Stop Number</label>
                                <select class="form-control" id="editStopIdx" name="editStopIdx">
                                    <option value="">Select Insert Index</option>
                                </select>
                            </div>
                        </div>
                        <div class="row route-title-row" style="display: none;">
                            <div class="col-md-12">
                                <label for="editRouteTitle">Route Title</label><label class="text-danger">*</label>
                                <span tabindex="0" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                    data-placement="right" title='Title for the new route'>
                                </span>
                                <input type="text" class="mb-3 form-control" id="editRouteTitle"
                                    placeholder="Title of new route" maxlength="255">
                            </div>
                        </div>
                        <div class="row route-description-row" style="display: none;">
                            <div class="col-md-12">
                                <label for="editRouteDescription">Route Description</label>
                                <span tabindex="0" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                    data-placement="right" title='Description for the new route'>
                                </span>
                                <input type="text" class="mb-3 form-control" id="editRouteDescription"
                                    placeholder="Description of new route" maxlength="800">
                            </div>
                        </div>
                    </div>

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
