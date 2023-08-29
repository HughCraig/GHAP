@push('styles')
    {{-- Include select2 widget. See https://github.com/select2/select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.min.css"
          integrity="sha512-kq3FES+RuuGoBW3a9R2ELYKRywUEQv0wvPTItv3DSGqjpbNtGWVdvT8qwdKkqvPzT93jp8tSF4+oN4IeTEIlQA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const uiServiceRoot = "{{ url('ajax/collections/' . $collection->id . '/datasets/addable') }}";
        const addDatasetToCollectionService = "{{ url('ajaxaddcollectiondataset') }}";
    </script>
    <script src="{{ asset('/js/addcollectiondatasetmodal.js') }}"></script>
@endpush

<button type="button" class="btn btn-primary mt-3 mb-3" data-toggle="modal" data-target="#addDatasetModal">Add a Layer</button>

<!-- MODAL popup -->
<div class="modal fade" id="addDatasetModal" data-collection-id="{{ $collection->id }}" tabindex="-1" role="dialog" aria-labelledby="addDatasetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="addDatasetModalLabel">Add a Layer</h3>

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Add from
                <div class="mb-4">
                    <label class="radio-inline">
                        <input type="radio" name="scopeRadioOptions" id="scopePublicLayersRadio" value="public" checked> All public layers
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="scopeRadioOptions" id="scopeUserLayersRadio" value="user"> My layers
                    </label>
                </div>


                Select a layer
                <div class="mb-4">
                    <select id="datasetSelect">
                        <option value=""></option>
                    </select>
                </div>

                <div id="layerInfo" style="min-height:60vh"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" id="submitAddDataset" type="button">Add</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
