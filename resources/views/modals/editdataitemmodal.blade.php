@include('modals.editglycerineimagemodal')

<div class="modal fade" id="editDataitemModal" tabindex="-1" role="dialog" aria-labelledby="editDataitemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="editDataitemModalLabel">
                Edit Place
                @include('templates.misc.contentdisclaimer')
            </h3>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body scrollable">
            <div class="scrollable">
                <div class="message-banner pt-4 pb-4 mb-3"></div>

                <label for="editTitle">Title</label><label class="text-danger">*</label>
                <input type="text" class="mb-3 form-control" id="editTitle" placeholder="Title" required>

                <div class="map-picker">
                    <p><small>Either enter coordinates or click on the map and apply.</small></p>

                    <label for="editLatitude">Latitude</label><label class="text-danger">*</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                          title="Either enter coordinates or click the map and 'apply'.">
                    </span>
                    <input type="text" class="mb-3 form-control mp-input-lat" id="editLatitude" placeholder="Latitude" required>

                    <label for="editLongitude">Longitude</label><label class="text-danger">*</label>
                    <input type="text" class="mb-3 form-control mp-input-lng" id="editLongitude" placeholder="Longitude" required>

                    <div class="mb-3">
                        <button type="button" class="btn btn-default btn-sm mp-btn-refresh" title="Refresh the map to reflect coordinate changes">Refresh Map</button>
                        <button type="button" class="btn btn-default btn-sm mp-btn-unset" title="Unset the coordinates">Unset</button>
                    </div>

                    <div class="mp-map"></div>
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

                <label for="editPlacename">Placename</label>
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title="Every item must have a Title. Placename is optional. If the purpose is to name a place put the Placename in the Title too.">
                    </span>
                <input type="text" class="mb-3 form-control" id="editPlacename" placeholder="Placename" required>

                <label for="editRecordtype">Record Type</label>
                <select class="w3-white form-control mb-3" id="editRecordtype" name="addrecordtype">
                    @foreach($recordtypes as $type)
                        <option value="{{$type}}">{{$type}}</option>
                    @endforeach
                </select>

                <div class="mb-3">
                    <label for="editDescription">Description</label>
                    <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="editDescription" placeholder="Description"></textarea>
                </div>
</div>


<!-- Dates -->
<h4 class="d-none d-lg-block mt-3">Dates</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionDatesEd"
  aria-expanded="false"
  aria-controls="sectionDatesEd">
  Dates
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionDatesEd" class="collapse d-lg-block">

                <div class="input-group date" id="editDateStartDiv">
                    <label for="editDatestart">Date Start</label><input type="text" class="mb-3 form-control input-group-addon" id="editDatestart" autocomplete="off"/>
                </div>
                <div class="input-group date" id="editDateEndDiv">
                    <label for="editDateend">Date End</label><input type="text" class="mb-3 form-control input-group-addon" id="editDateend" autocomplete="off">
                </div>

</div>
<!-- Image -->
<h4 class="d-none d-lg-block mt-3">Image</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionImageEd"
  aria-expanded="false"
  aria-controls="sectionImageEd">
  Image
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionImageEd" class="collapse d-lg-block">

                <!-- Image Upload -->
                <label for="editImage">Image</label> 
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title='Max upload size {{ floor(config("app.max_upload_image_size") / (1024 * 1024)) . " MB" }}'>
                </span>
                <div id="editImageContainer" class="mb-3" style="display: none;">
                    <img id="editImagePreview" src="#" alt="Place Image" style="max-height: 150px;">
                </div>
                <div id="deleteImageContainer" style="display: none;">
                    <input type="checkbox" id="deletePlaceImage">
                    <label class="pr-4" for="deletePlaceImage">Delete current image</label>
                </div>
                <input type="file" class="form-control" id="editImage" name="image" accept="image/*">

                <!-- Glycerine Image -->
                
                    <label for="editGlycerineImageButton" class="mt-4">Glycerine Image</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title='IIIF images for integrating with the Glycerine application.'>
                    </span>
                    <div id="glycerine-url-container" style="display: none;">
                    </div>
                    <div><button type="button" class="btn btn-default btn-sm mb-3" id="editGlycerineImageButton">Edit Glycerine Image</button></div>
                

                
</div>
<!-- Reference -->
<h4 class="d-none d-lg-block">Reference</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionReferenceEd"
  aria-expanded="false"
  aria-controls="sectionReferenceEd">
  Reference
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionReferenceEd" class="collapse d-lg-block">


<!-- Linkback with validator-->
                <label for="editExternalurl">Linkback (URL)</label>
                <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title="This is a special link shown in the map pop up to link to another web page about this place. Enter the URL only, starting with https://"></span>
                <input type="text" class="mb-3 form-control" id="editExternalurl" placeholder="Linkback">

                <div class="mb-3">
                    <label for="editSource">Source</label>
                    <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                      title="Website url, ISBN, Book title, etc.">
                    </span>
                    <textarea rows="3" class="mb-3 form-control w-100 wysiwyg-editor" id="editSource" placeholder="Source"></textarea>
                </div>

</div>
<!-- REGION -->
<h4 class="d-none d-lg-block mt-3">Region</h4>
<button
  class="btn btn-outline-secondary w-100 text-start d-lg-none mb-2 mt-3"
  type="button"
  data-bs-toggle="collapse"
  data-bs-target="#sectionRegionEd"
  aria-expanded="false"
  aria-controls="sectionRegionEd">
  Region
  <span class="bi bi-caret-down-fill float-end"></span>
</button>

<div id="sectionRegionEd" class="collapse d-lg-block">

                <label for="editFeatureterm">Feature Term
                    <a href="/guides/featureterms.php" target="_blank">
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                                  title="Specific terms for land features such as mountains, lakes, hills, etc. Start typing and choose from the list.">
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

                <label for="editLga">LGA
                    <a href="/guides/lgas.php" target="_blank">
                            <span tabindex="0" data-bs-html="true" data-bs-animation="true" class="bi bi-question-circle" data-bs-toggle="tooltip" data-bs-placement="right"
                                  title="Australian Local Government Areas. Start typing and choose from the list.">
                            </span>
                    </a>
                </label>
                <input type="text" class="mb-3 form-control" id="editLga" placeholder="LGA">

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
            <button type="button" class="btn btn-primary" id="editDataitemSaveButton">Save</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>
