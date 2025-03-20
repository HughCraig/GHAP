@push('scripts')
    <script>
        const text_max_upload_file_size = {{config('app.text_max_upload_file_size')}}
    </script>
    <script src="{{ asset('/js/contributesource.js') }}"></script> 
@endpush

<!-- MODAL popup -->


@include('modals.userparsetextmodal')

<input type="hidden" id="csrfToken" value="{{ csrf_token() }}">

<div class="modal fade" id="contributesource" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content p-3">
            
            <div class="modal-header">
                <label>Choose source</label><br>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="d-flex justify-content-center align-items-center pt-4">
                <input class="mr-2" type="radio" name="source" value="json"> GeoJSON
                <input class="ml-4 mr-2" type="radio" name="source" value="kml"> KML
                <input class="ml-4 mr-2" type="radio" name="source" value="csv"> CSV
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign ml-1" id="sourceguide" data-toggle="tooltip" data-placement="right"
                    title="Required Fields/Columns: 'title' or 'placename', 'latitude', 'longitude'
                        Recommended Fields/Columns: description, datestart, dateend, linkback (a link to this record on another website)"></span>
                <input class="ml-4 mr-2" type="radio" name="source" value="text"> Text
            </div>

            <div id="modal-content" class="mt-3"></div>
            

            <div class="modal-footer" style="background-color: white;">
                <button type="button" class="btn btn-primary" id="sourcesavebtn">Save</button>
            </div>
        </div>
    </div>
</div>