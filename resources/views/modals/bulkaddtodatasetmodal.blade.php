@push('scripts')
    <script type="text/javascript" src="{{ asset('/js/bulkaddtodatasetmodal.js') }}"></script>
@endpush
<button type="button" class="mt-3 mb-3 btn btn-primary" data-toggle="modal" data-target="#bulkaddModal">Import from
    file</button>
<span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
    data-placement="right"
    title="You can add points one by one, or upload a CSV, KML or GeoJSON file to import many points.">
</span>
<!-- MODAL popup -->
<div class="modal fade" id="bulkaddModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">
                    Upload File
                    @include('templates.misc.contentdisclaimer')
                </h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ url('bulkadddataitem') }}" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="ds_id" id="ds_id" value="{{ $ds->id }}" />
                    <div class="mb-4">
                        <span class="text-danger">Required Fields/Columns:</span> 'title' or 'placename', 'latitude',
                        'longitude'
                    </div>
                    <div class="mb-4">
                        <span class="text-success">Recommended Fields/Columns:</span> description, datestart, dateend,
                        linkback (a link to this record on another website)
                    </div>
                    <div class="mb-4">Check the <a href="https://tlcmap.org/guides/ghap/#contribute"
                            target="_blank">guide</a> for more information on creating and uploading CSV, KML, and
                        GeoJSON files.</div>
                    <div class="border p-4">
                        Select file to upload (.csv .kml or .json):
                        <input type="file" id="fileToUpload" name="fileToUpload"
                            accept=".csv,.kml,.json,.geojson,application/json,application/vnd.google-earth.kml+xml,text/csv"
                            required>
                    </div>
                    <h4>For KML uploads only:</h4>
                    <div class="form-group">
                        <div><label for="overwriteStyle">Append existing style data with the style data in this
                                KML?</label>
                            <input type="checkbox" id="overwriteStyle" name="appendStyle" checked>
                        </div>
                        <div><label for="overwriteJourney">Overwrite existing journey (Track) data with the journey data
                                in
                                this KML?</label> <input type="checkbox" id="overwriteJourney" name="overwriteJourney"
                                checked></div>
                    </div>
                    <!-- New section for mobility dataset -->
                    <h4>For Mobility Dataset Upload</h4>
                    <div class="border p-4">
                        <div class="form-group">
                            <label for="uploadMobDatasetWithRoute">Upload mobility dataset with
                                routes?</label>
                            <input type="checkbox" id="uploadMobDatasetWithRoute" name="uploadMobDatasetWithRoute">
                        </div>
                        <ul id="mobilityRouteOptions" style="display: none;">
                            <li class="form-group">
                                <label for="isODPairs">Is this dataset in origin-destination pairs
                                    format?</label>
                                <input type="checkbox" id="isODPairs" name="isODPairs">
                                <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                    title="Origin-Destination pairs represent start and end points of movements, without intermediate points."></span>
                            </li>
                            <li class="form-group">
                                <label>What do you want to do with this mobility
                                    dataset?</label>
                                <div>
                                    <input type="radio" id="addNewPlaces" name="datasetPurpose" value="addNewPlaces">
                                    <label class="font-weight-normal" for="addNewPlaces">Add new places</label>
                                    <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                        title="Add new places: Add isolated existing places or new places into newly created routes or insert into them existing ones."></span>
                                </div>
                                <div>
                                    <input type="radio" id="reorderRoutes" name="datasetPurpose"
                                        value="reorderRoutes">
                                    <label class="font-weight-normal" for="reorderRoutes">Reorder
                                        places
                                        within individual routes
                                    </label>
                                    <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                        title="Reorder points within routes: Maintain original points, no exchanges between routes."></span>
                                </div>
                                <div>
                                    <input type="radio" id="reorganizeRoutes" name="datasetPurpose"
                                        value="reorganizeRoutes">
                                    <label class="font-weight-normal" for="reorganizeRoutes">Reorganize places
                                        across multiple
                                        routes</label>
                                    <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip"
                                        title="Customize routes: Reorganize places within existing routes, optionally create new routes, and add new places. Reorder existing places or combine with new ones in original or new routes."></span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <input class="btn btn-primary" type="submit" value="Upload File" name="submit">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>
