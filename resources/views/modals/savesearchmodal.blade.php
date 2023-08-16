<button id="saveSearchModalButton" type="button" class="mt-3 mb-3 btn btn-primary" data-toggle="modal" data-target="#saveSearchModal">Save your search</button>
<!-- MODAL popup -->
<div class="modal fade" id="saveSearchModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="exampleModalLabel">Save your search</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="message-banner"></div>
            <div class="flexdiv">
                <input type="hidden" id="save_search_query" value="{{ substr(url()->full(),strpos(url()->full(),'?')) }}" />
                <input type="hidden" id="save_search_count" value="{{ $details->total() }}" />
                <input type="text" class="smallerinputs w3-white form-control" id="save_search_name" placeholder="Name your search" maxlength="20"/>
                <span class="red-asterisk">*</span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" id="save_search_button" type="button">Save your search</button>
            <button type="button" class="btn btn-secondary" id="saveSearchCloseButton" data-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>