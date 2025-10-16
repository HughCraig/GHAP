<button type="button" class="mt-3 mb-3 btn btn-primary" data-toggle="modal" data-target="#destroyLinksModal">Destroy Share Links</button>
<!-- MODAL popup -->
<div class="modal fade" id="destroyLinksModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="exampleModalLabel">Destroying all share links</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <span class="d-block mb-4">Are you sure you want to destroy ALL current collaborator share links for this dataset?</span>
            
            <span class="text-danger">NOTE: This will not remove already approved collaborators</span>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="delete_share_links_button" data-dismiss="modal">Yes, Destroy all share links</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>