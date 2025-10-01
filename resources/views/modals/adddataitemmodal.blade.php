@push('scripts')

<script>
    var ajaxadddataitem = "{{url('ajaxadddataitem')}}";
    const max_upload_image_size = {{ config('app.max_upload_image_size') }};
</script>


<script src="{{ asset('js/extended-data-editor.js') }}"></script>

<script src="{{ asset('/js/dataitem.js') }}"></script>
<script src="{{ asset('js/adddataitemmodal.js') }}"></script>

@endpush

@include('modals.addglycerineimagemodal')

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    Add a place to TLCMap
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body scrollable">
                <div class="scrollable">
                    <div class="message-banner"></div>

                    <p>
                        First. choose a layer. Every place in TLCMap belongs to a layer. You can add more places to this layer later.
                    </p>

                    <div class="mb-2" style="font-weight: bold;">
                        Layer<label class="text-danger">*</label>
                        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="A place must be part of a 'Layer'. A Layer might be places about a certain topic, or simply 'My Places'. You must create a Layer to add places to it. You can change Layer details later after you create it. You will return to this screen after creating a new layer."></span>
                        <button id="addNewLayer" style="float: right;" type="button" data-bs-toggle="modal" data-bs-target="#newLayerModal">New layer</button>
                    </div>

                    <div class="mb-4">
                        <select id="chooseLayer" style="width: 100%; height: 30px;">
                            <option value=""></option>
                        </select>
                    </div>

                    <label for="addtitle">Title</label><label class="text-danger">*</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="A short title for the information you are adding about this place."></span>
                    <input type="text" class="mb-3 form-control" id="addtitle" placeholder="Title" required>

                    <label for="addplacename">Placename</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Every item must have a Title and Placename is optional. If the purpose is to name a place, then put the Placename in the Title too.">
                    </span>
                    <input type="text" class="mb-3 form-control" id="addplacename" placeholder="Placename" required>

                    <!-- Image Upload -->
                    <label for="addImage">Image</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
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
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Use decimal coordinates. For adding many places,try <a href='https://tlcmap.org/quicktools/quickcoordinates.html' target='_blank'>Quick Coordinates</a> and upload a CSV."></span>
                        <input type="text" class="mb-3 form-control mp-input-lat" id="addlatitude" placeholder="Latitude" required>

                        <label for="addlongitude">Longitude</label><label class="text-danger">*</label>
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Use decimal coordinates. For adding many places,try <a href='https://tlcmap.org/quicktools/quickcoordinates.html' target='_blank'>Quick Coordinates</a> and upload a CSV."></span>
                        <input type="text" class="mb-3 form-control mp-input-lng" id="addlongitude" placeholder="Longitude" required>

                        <div class="mb-3">
                            <button type="button" class="btn btn-default btn-sm mp-btn-refresh" title="Refresh the map to reflect coordinate changes">Refresh Map</button>
                            <button type="button" class="btn btn-default btn-sm mp-btn-unset" title="Unset the coordinates">Unset</button>
                        </div>

                        <div class="mp-map"></div>
                    </div>

                    <label for="addrecordtype">Record Type</label>
                    <select class="w3-white form-control mb-3" id="addrecordtype" name="addrecordtype">
                        @foreach($recordtypes as $recordtype)
                        <option label="{{$recordtype->type}}">{{$recordtype->type}}</option>
                        @endforeach
                    </select>

                    <div class="mb-3">
                        <label for="adddescription">Description</label>
                        <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="adddescription" placeholder="Description"></textarea>
                    </div>

                    <label for="addfeatureterm">Feature Term
                        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="A list of feature terms for types of places such as mountains, towns or rivers, used by government bodies. Start typing to see a list of terms."></span>
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
                        <label for="adddatestart">Date Start
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Type a year, date, or select from calendar"></span>
                        </label>
                        <input type="text" class="mb-3 form-control input-group-addon" id="adddatestart" autocomplete="off" />
                    </div>
                    <div class="input-group date" id="addDateEndDiv">
                        <label for="adddateend">Date End
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Type a year, date, or select from calendar"></span>
                        </label>
                        <input type="text" class="mb-3 form-control input-group-addon" id="adddateend" autocomplete="off">
                    </div>

                    <label for="addlga">LGA</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Local Government Area"></span>               
                    <input type="text" class="mb-3 form-control" id="addlga" placeholder="LGA">

                    <!-- Linkback with validator-->
                    <label for="addexternalurl">Linkback (URL)
                        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="This is the main link for more information on another website about this place. Eg: a newspaper article, record in a museum database, video of a story, organisations website, etc. It must start with http:// or https://"></span>
                    </label>
                    <input type="text" class="mb-3 form-control" id="addexternalurl" placeholder="Linkback">

                    <div class="mb-3">
                        <label for="addsource">Source (Website url, ISBN, Book title, etc)
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Please add the citation or source of this information, to ensure it is reliable and so others can follow the references."></span>
                        </label>
                        <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="addsource" placeholder="Source"></textarea>
                    </div>

                    <input type="hidden" id="related_place_uid">

                    <!-- Extended data editor -->
                    @include('editors.extended_data_editor')

                </div>
            </div>
            <div class="modal-footer">
                <span class="text-danger">* required fields</span>
                <button type="button" class="btn btn-primary" id="add_place_button_submit">Add Place</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>