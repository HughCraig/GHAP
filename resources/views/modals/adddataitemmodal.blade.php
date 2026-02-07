@push('scripts')

<script>
    var ajaxadddataitem = "{{url('ajaxadddataitem')}}";
    
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
                        First, create or choose your map layer. Every place is in a layer. You can add more places to the layer.
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

                    <div class="map-picker">
                        <p><small>Either enter coordinates manually or click on the map and 'apply'.</small></p>

                        <label for="addlatitude">Latitude</label><label class="text-danger">*</label>
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Use decimal coordinates. For adding many places,try <a href='https://tlcmap.org/quicktools/quickcoordinates.html' target='_blank'>Quick Coordinates</a> and upload a CSV."></span>
                        <input type="text" class="mb-3 form-control mp-input-lat" id="addlatitude" placeholder="Latitude" required>

                        <label for="addlongitude">Longitude</label><label class="text-danger">*</label>
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Use decimal coordinates. For adding many places,try <a href='https://tlcmap.org/quicktools/quickcoordinates.html' target='_blank'>Quick Coordinates</a> and upload a CSV."></span>
                        <input type="text" class="mb-3 form-control mp-input-lng" id="addlongitude" placeholder="Longitude" required>

                        <div class="mb-3">
                            <button type="button" class="btn btn-secondary btn-sm mp-toggle-fullscreen">Fullscreen map</button>
                            <button type="button" class="btn btn-default btn-sm mp-btn-refresh" title="Refresh the map to reflect coordinate changes">Refresh Map</button>
                            <button type="button" class="btn btn-default btn-sm mp-btn-unset" title="Unset the coordinates">Unset</button>
                        </div>

                        <div class="mp-map"></div>
                        <button type="button" class="mp-fs-close">×</button>
                    </div>

<!-- Description -->
<h4 class="d-none d-lg-block mt-3">Description</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionDespription"
  aria-expanded="false"
  aria-controls="sectionDespription">
  Description
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionDespription" class="collapse d-lg-block">

                    <label for="addplacename">Placename</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title="Every item must have a Title. Placename is optional. If the purpose is to name a place put the Placename in the Title too.">
                    </span>
                    <input type="text" class="mb-3 form-control" id="addplacename" placeholder="Placename" required>



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

</div>

<!-- Dates -->
<h4 class="d-none d-lg-block mt-3">Dates</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionDates"
  aria-expanded="false"
  aria-controls="sectionDates">
  Dates
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionDates" class="collapse d-lg-block">


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


</div>
<!-- Image -->
<h4 class="d-none d-lg-block mt-3">Image</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionImage"
  aria-expanded="false"
  aria-controls="sectionImage">
  Image
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionImage" class="collapse d-lg-block">


                    <!-- Image Upload -->
                    <label for="addImage">Image</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
                    </span>
                    <input type="file" class="form-control" id="addImage" name="image" accept="image/*">

                    <!-- Glycerine Image -->
                    <label for="addGlycerineImageButton" class="mt-4">Glycerine Image</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title='IIIF images for integrating with the Glycerine application.'>
                    </span>
                    <div id="add-glycerine-url-container" style="display: none;">
                    </div>
                    <div>
                        <button type="button" class="btn btn-default btn-sm mb-3" id="addGlycerineImageButton">Add Glycerine Image</button>
                    </div>

</div>
<!-- Reference -->
<h4 class="d-none d-lg-block">Reference</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionReference"
  aria-expanded="false"
  aria-controls="sectionReference">
  Reference
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionReference" class="collapse d-lg-block">


                    <!-- Linkback with validator-->
                    <label for="addexternalurl">Linkback (URL)
                        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title="This is a special link shown in the map pop up to link to another web page about this place. Enter the URL only, starting with https://"></span>
                    </label>
                    <input type="text" class="mb-3 form-control" id="addexternalurl" placeholder="Linkback">

                    <div class="mb-3">
                        <label for="addsource">Source
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                        title="Website url, ISBN, Book title, etc.">
                        </span></label>
                        <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="addsource" placeholder="Source"></textarea>
                    </div>


</div>
<!-- REGION -->
<h4 class="d-none d-lg-block mt-3">Region</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionRegion"
  aria-expanded="false"
  aria-controls="sectionRegion">
  Region
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionRegion" class="collapse d-lg-block">

                    <label for="addfeatureterm">Feature Term
                        <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                                  title="Specific terms for land features such as mountains, lakes, hills, etc. Start typing and choose from the list.">
                            </span>
                    </label>
                    <input type="text" class="mb-3 form-control" id="addfeatureterm" placeholder="Feature Term">

                    <label for="addstate">State</label>
                    <select class="w3-white form-control mb-3" name="addstate" id="addstate">
                        <option label selected></option>
                        @foreach($states as $state)
                        <option label="{{$state}}">{{$state}}</option>
                        @endforeach
                    </select>

                    <label for="addlga">LGA</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                                  title="Australian Local Government Areas. Start typing and choose from the list.">
                            </span>
                    <input type="text" class="mb-3 form-control" id="addlga" placeholder="LGA">

<!-- Important. This is for when someone selects a place and adds information to it. This is stored in the db so that we can show places and information related to each other -->
                    <input type="hidden" id="related_place_uid">

</div>
<!-- Ext Data -->
<h4 class="d-none d-lg-block mt-3">Extended Data</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionExtData"
  aria-expanded="false"
  aria-controls="sectionExtData">
  Extended Data
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionExtData" class="collapse d-lg-block">

                    <!-- Extended data editor -->
                    @include('editors.extended_data_editor')

</div>


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