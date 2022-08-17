<button type="button" class="mt-3 mb-3 btn btn-primary" data-toggle="modal" data-target="#bulkaddModal">Import from file</button>
<span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
                        title="You can add points one by one, or upload a CSV, KML or GeoJSON file to import many points.">
</span>
<!-- MODAL popup -->
<div class="modal fade" id="bulkaddModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="exampleModalLabel">Upload File</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{url('bulkadddataitem')}}" method="post" enctype="multipart/form-data">
            <div class="modal-body">
                @include('templates.misc.contentdisclaimer')
                @csrf
                <input type="hidden" name="ds_id" id="ds_id" value="{{$ds->id}}" />
                <div class="mb-4">
                    <span class="text-danger">Required Fields/Columns:</span> 'title' or 'placename', 'latitude', 'longitude'
                </div>
                <div class="mb-4">
                    <span class="text-success">Recommended Fields/Columns:</span> description, datestart, dateend, linkback (a link to this record on another website)
                </div>
                <div class="mb-4">Check the <a href="/guides/ghap/#contribute" target="_blank">guide</a> for more information on creating and uploading CSV, KML, and GeoJSON files.</div>
                <div class="border p-4">
                    Select file to upload (.csv .kml or .json):
                    <input type="file" id="fileToUpload" name="fileToUpload" required>
                </div>
                <p>For KML uploads only:</p>
                <div><label for="overwriteStyle">Append existing style data with the style data in this KML?</label> <input type="checkbox" id="overwriteStyle" name="appendStyle" checked></div>
                <div><label for="overwriteJourney">Overwrite existing journey (Track) data with the journey data in this KML?</label> <input type="checkbox" id="overwriteJourney" name="overwriteJourney" checked></div>
            </div>
            <div class="modal-footer">
                <input class="btn btn-primary" type="submit" value="Upload File" name="submit" accept=".csv,.kml,.json,.geojson,application/json,application/vnd.google-earth.kml+xml,text/csv">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </form>
        </div>
    </div>
</div>
