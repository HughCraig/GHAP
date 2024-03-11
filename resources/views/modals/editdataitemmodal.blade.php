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

                    <label for="editRecordtype">Record Type</label>
                    <select class="w3-white form-control mb-3" id="editRecordtype" name="addrecordtype">
                        @foreach ($recordtypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>

                    <div class="mb-3">
                        <label for="editDescription">Description</label>
                        <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="editDescription" placeholder="Description"></textarea>
                    </div>

                    <!-- Edit Quantity-->
                    <label for="editQuantity">Quantity</label>
                    <span tabindex="0" data-html="true" data-animation="true"
                        class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="Please ensure you enter an integer greater or equal to 0. <br> Clearing the box will erase the quantity value for this place."></span>
                    <input type="text" class="mb-3 form-control" id="editQuantity" placeholder="10">
                    {{-- TODO: Not sure how can I validate the number properliy, use the string validation like coordinates for now. --}}
                    {{-- <input type="number" min="0" step="1" class="mb-3 form-control" id="editQuantity" placeholder="10"> --}}

                    <!-- Route Info-->
                    <div class="route-info border p-3">
                        <p><small>Edit the route information if you want to set a place into an exsiting route or create
                                a place for a new route.</small></p>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="editRouteId">
                                    Route ID</label>
                                <span tabindex="0" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                    data-placement="right"
                                    title="If you want to edit the place to an exsiting route in GHAP, use the existing Route ID .
                                    If you want to edit the place a new route in GHAP, use new Route ID"></span>
                                <input type="text" class="mb-3 form-control" id="editRouteId"
                                    placeholder="Route ID Shown in GHAP">
                            </div>
                            <div class="col-md-6">
                                <label for="editRouteOriId">Route Original ID</label>
                                <span tabindex="1" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                    data-placement="right"
                                    title="The Route Original ID will be searched in the exsiting original Route IDs to find the existing system-generated Route ID in this layer.
                                    If there is nothing found, a new GHAP Route ID will be generated for this original Route ID"></span>
                                <input type="text" class="mb-3 form-control" id="editRouteOriId"
                                    placeholder="Route ID in Your Dataset">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <label for="editRouteTitle">Route Title</label>
                                <span tabindex="1" data-html="true" data-animation="true"
                                    class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                    data-placement="right"
                                    title="The Route Title will be searched in the exsiting original Route Titles to find the existing Route Titles in this layer. If there is nothing found, a new GHAP Route ID will be generated for this Route Title."></span>
                                <input type="text" class="mb-3 form-control" id="editRouteTitle"
                                    placeholder="Route Title in Your Dataset">
                            </div>
                        </div>
                    </div>

                    <label for="editFeatureterm">Feature Term
                        <a href="/guides/featureterms.php" target="_blank">
                            <span tabindex="0" data-html="true" data-animation="true"
                                class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                data-placement="right" title="Click here for information on valid feature terms">
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
