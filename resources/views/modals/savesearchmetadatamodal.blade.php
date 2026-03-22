@push('scripts')
<!-- CSRF Token -->
<script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>
<script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>

@endpush


<!-- search_modal.blade.php -->
<div class="modal fade" id="savesearchmetadatamodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="name"></h3>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <h3 class="ml-3">Search Metadata</h3>
            <div class="row mt-3 ml-1 mr-1">
                <div class="col-lg-4">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr style="height: 50px; overflow: auto">
                                <th>Description</th>
                                <td id="description"></td>
                            </tr>
                            <tr style="height: 50px; overflow: auto">
                                <th>Search Type</th>
                                <td id="type"></td>
                            </tr>
                            <tr>
                                <th>Content Warning</th>
                                <td id="warning"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="table-responsive" style="overflow: unset">
                        <table class="table table-bordered">
                            <tr>
                                <th class="w-25">Subject</th>
                                <td id="subject"></td>
                            </tr>
                            <tr>
                                <th>Date From</th>
                                <td id="temporal_from"></td>
                            </tr>
                            <tr>
                                <th>Date To</th>
                                <td id="temporal_to"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th class="w-25">Latitude From</th>
                                <td id="latitude_from"></td>
                            </tr>
                            <tr>
                                <th>Longitude From</th>
                                <td id="latitude_to"></td>
                            </tr>
                            <tr>
                                <th>Latitude To</th>
                                <td id="longitude_from"></td>
                            </tr>
                            <tr>
                                <th>Longitude To</th>
                                <td id="longitude_to"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <h3 class="ml-3">Search Params</h3>
            <div class="row mt-3 ml-1 mr-1">
                <div class="col">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="search_query">
                        </table>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>