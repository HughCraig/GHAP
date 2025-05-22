@push('scripts')
<script src="{{ asset('js/addglycerineimage.js') }}"></script>
@endpush

<!-- MODAL popup -->
<div class="modal fade" id="addGlycerineImageModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">
                    Add Glycerine Image
                </h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body scrollable">
                <div class="scrollable">
                    <div class="form-group">
                        <label for="iiifManifestInput">IIIF Manifest (Collection)</label>
                        <input type="text" class="form-control" id="iiifManifestInput" placeholder="Enter IIIF Collection URL">
                        <small class="form-text text-muted">Paste a Collection-level IIIF manifest URL here.</small>
                    </div>

                    <button type="button" class="btn btn-primary mb-3" id="loadManifest">Load</button>

                    <div id="imageSetsContainer" class="row"></div>
                    <div id="imagesContainer" class="row mt-4"></div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="text-danger">* required fields</span>
                <button type="button" class="btn btn-primary" id="add_glycerine_image_submit">Add Glycerine Image</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="add_glycerine_image_close">Close</button>
            </div>
        </div>
    </div>
</div>

<div id="loadingWheel-contribute" class="loadingWheel">
    <div class="spinner"></div>
</div>