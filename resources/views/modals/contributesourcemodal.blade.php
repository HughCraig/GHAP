@push('scripts')
    <script src="{{ asset('/js/contributesource.js') }}"></script> 
@endpush

<!-- MODAL popup -->


@include('modals.userparsetextmodal')

<input type="hidden" id="csrfToken" value="{{ csrf_token() }}">

<div class="modal fade" id="contributesource" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content p-3">
            
            <label>Choose source</label><br>

            <div class="d-flex justify-content-center align-items-center">
                <input class="mr-2" type="radio" name="source" value="json"> JSON
                <input class="ml-4 mr-2" type="radio" name="source" value="kml"> KML
                <input class="ml-4 mr-2" type="radio" name="source" value="csv"> CSV
                <input class="ml-4 mr-2" type="radio" name="source" value="text"> Text
            </div>

            <div id="modal-content" class="mt-3"></div>
            

            <div class="modal-footer" style="background-color: white;">
                <button type="button" class="btn btn-primary" id="sourcesavebtn">Save</button>
            </div>
        </div>
    </div>
</div>