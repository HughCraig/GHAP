<!-- MODAL popup -->
<div class="modal fade" id="addCollaboratorModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="exampleModalLabel">Adding Collaborator</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            Role given to those with this link
            <select id="dsrole_selector" class="border d-block w-100 mb-5 mt-1">
                <option value="ADMIN">Admin</option>
                <option value="COLLABORATOR">Collaborator</option>
                <option value="VIEWER" selected>Viewer</option>
            </select>
            <span class="d-block">Have your collaborator paste this into their URL bar:</span>
            <div id="viaurl" class="mt-1 mb-5">
                <input id="share_link" type="text" readonly="readonly" placeholder="Share link will appear here" class="border">
                <button type="button" class="btn btn-primary" id="generate_share_link_button">Generate Link</button>
            </div>
            We can also email this link to your collaborator for you!
            <div id="viaemail" class="mt-3">
                <input type="text" class="mb-1 border" id="collaboratoremail" placeholder="Enter collaborator's email address">
                <button class="btn btn-primary" type="button" id="emailcollaboratorbutton" disabled>Send</button>
                <input type="hidden" id="senderemail" value="{{$user->email}}">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="collaboratorclosebutton" data-dismiss="modal">Close</button>
        </div>
        </div>
    </div>
</div>