@push('scripts')
<script src="{{ asset('js/editglycerineimage.js') }}"></script>
@endpush

<!-- MODAL popup -->
<div class="modal fade" id="editGlycerineImageModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">
                    Edit Glycerine Image
                </h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body scrollable">
                <div class="scrollable">
                    <div class="form-group">
                        <label for="editIiifManifestInput">IIIF Manifest (Collection)</label>
                        <input type="text" class="form-control" id="editIiifManifestInput" placeholder="Enter IIIF Collection URL">
                        <small class="form-text text-muted">Paste a Collection-level IIIF manifest URL here.</small>
                    </div>

                    <button type="button" class="btn btn-primary mb-3" id="editLoadManifest">Load</button>

                    <div id="editImageSetsContainer" class="row"></div>
                    <div id="editImagesContainer" class="row mt-4"></div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="text-danger">* required fields</span>
                <button type="button" class="btn btn-primary" id="edit_glycerine_image_submit">Add Glycerine Image</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="edit_glycerine_image_close">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="edit-loadingWheel-contribute" class="loadingWheel">
    <div class="spinner"></div>
</div>