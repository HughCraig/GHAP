
@push('scripts')
    <script>
        const ajaxGetUserSavedSearchesURL = "{{ route('ajax.saved-searches') }}";
        const ajaxAddSavedSearchesURL = "{{ route('ajax.add-saved-search') }}";
    </script>
    <script src="{{ asset('js/message-banner.js') }}"></script>
    <script src="{{ asset('/js/addcollectionsavesearch.js') }}"></script>
@endpush

<button type="button" class="btn btn-primary mt-3 mb-3" data-bs-toggle="modal" data-bs-target="#addSavedSearchModal">Add a saved search</button>

<!-- Add saved search MODAL popup -->
<div class="modal fade" id="addSavedSearchModal" data-collection-id="{{ $collection->id }}" tabindex="-1" role="dialog" aria-labelledby="addSavedSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="addDatasetModalLabel">Add a Saved Search</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="message-banner"></div>
                Select a saved search
                <div class="mb-4">
                    <select id="savedSearchSelect">
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" id="submitAddSavedSearch" type="button">Add</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>