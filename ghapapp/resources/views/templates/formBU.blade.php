<!-- https://jqueryui.com/autocomplete/ -->
<script>
    //Put the relative URL of our ajax functions into global vars for use in external .js files
    var bulkfileparser = "{{url('bulkfileparser')}}";

    //LGA Autocomplete
    $(document).ready(function(){
        var lgas = {!! $lgas !!};
        $( "#lga" ).autocomplete({
            source: function(request, response) {
                var results = $.ui.autocomplete.filter(lgas, request.term);
                response(results.slice(0, 20)); //return only 20 results
        }
        }); 
    });

    //parish autocomplete
    $(document).ready(function(){
        var parishes = {!! $parishes !!};
        $( "#parish, [name='parish']" ).autocomplete({
            source: function(request, response) {
                var results = $.ui.autocomplete.filter(parishes, request.term);
                response(results.slice(0, 17)); //return only 20 results
            }
        }); 
        $( "#addparish, [name='parish']" ).autocomplete( "option", "appendTo", ".eventInsForm" );
    });

    //feature_term autocomplete
    $(document).ready(function(){
            var feature_terms = {!! $feature_terms !!};
            $( "#feature_term, [name='feature_term']" ).autocomplete({
                source: function(request, response) {
                    var results = $.ui.autocomplete.filter(feature_terms, request.term);
                    response(results.slice(0, 15)); //return only 20 results
                }
            }); 
            $( "#addfeatureterm, [name='feature_term']" ).autocomplete( "option", "appendTo", ".eventInsForm" );
        });

      

    //Bootstrap tooltips
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });

</script> 
<link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
<script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>


<div class="searchForm">
    <form action="search" autocomplete="off" method="GET" role="search" id="searchForm" name="searchForm" enctype="multipart/form-data">    
        <!-- Search bar -->
        <div id="mainsearchdiv" class="mb-3">
            <input type="text" class="form-control" name="fuzzyname" id="input" placeholder="Enter search">
            <select class="form-control" id="input-select-box" onchange="changeInput(this);">
                <option value="containsname" selected="selected">Contains</option>
                <option value="fuzzyname">Fuzzy</option>
                <option value="name">Exact Match</option>
                <option value="anps_id">anps_id</option>
            </select>
            <button class="btn btn-primary" type="button" id="gazformbutton" onclick="submitSearchForm();">
                Search <i class="fa fa-search"></i>
	    </button>
<span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right"
style="font-size: 32px;" title="Find results within the gazetteer and/or community contributed humanities information. Try 'contains', 'fuzzy' or 'exact'. Or use filters below, simply select an area on the map, or combine searches and filters. Max 50,000 results per page or file export.">
    </span>
        </div>

        <!-- Exact match and Download check buttons -->
        <div class="row">
            <div class="col-xl-5 offset-xl-1 sub-filter-div">
                <h4 class="mt-1">Filters</h4>
                <div class="row">

                </div>
            </div>
            <div class="col-xl-5 sub-filter-div">
                <h4 class="mt-1">Sources</h4>
                <div class="row">
                    <div class="col-sm"><label data-toggle="tooltip" title="Search ANPS aggregated gazetteer of 'official' place names.">Gazetteer <input type="checkbox" id="searchausgaz" name="searchausgaz" checked></label></div>
                    <div class="col-sm"><label data-toggle="tooltip" title="Search layers from research and user community.">Layers <input type="checkbox" id="searchpublicdatasets" name="searchpublicdatasets" checked></label></div>
                </div>
            </div>
        </div>

        <!-- Filters, now as rows instead of dropdown -->
        <div class="row">
            <div class="col-xl-2 col-md-6 offset-xl-1 w3-light-grey filter-div">
                <p class="h3 m-2 mb-3 text-center">Additional Filters</p>
                <div class="row align-items-center my-auto"  data-toggle="tooltip" title="Local Government Area."><div class="col-sm-4">LGA:</div><div class="col-sm-8"><input type="text" class="w3-white form-control" name="lga" id="lga" autocomplete="off"></div></div>
				<div class="row align-items-center my-auto"><div class="col-sm-4">State:</div><div class="col-sm-8"><select class="w3-white form-control" name="state" id="state">
				<option label="" selected></option>
				@foreach($states as $state)
                                <option label="{{$state->state_code}}">{{$state->state_code}}</option>
                                @endforeach
                            </select></div></div>
                <div class="row align-items-center my-auto"><div class="col-sm-4">Parish:</div><div class="col-sm-8"><input type="text" class="w3-white form-control" name="parish" id="parish" autocomplete="off"></div></div>
                <div class="row align-items-center my-auto"><div class="col-sm-4"  data-toggle="tooltip" title="Not all places are tagged with their feature for all states, so this will return only partial results for some areas.">Feature Term:</div><div class="col-sm-8"><input type="text" class="w3-white form-control" name="feature_term" id="feature_term" autocomplete="off"></div></div>
                
                <div class="row align-items-center my-auto"><div class="col-sm-4">From ID:</div><div class="col-sm-8"><input type="text" class="smallerinputs w3-white form-control" id="from" name="from"></div></div>
                <div class="row align-items-center my-auto"><div class="col-sm-4">To ID:</div><div class="col-sm-8"><input type="text" class="w3-white form-control" id="to" name="to"></div></div>
                <div class="row align-items-center my-auto" data-toggle="tooltip" title="Places without dates associated are not included."><div class="col-sm-4">Date From:</div><div class="col-sm-8"><input type="text" class="smallerinputs w3-white form-control" id="datefrom" name="datefrom"></div></div>
                <div class="row align-items-center my-auto"><div class="col-sm-4">Date To:</div><div class="col-sm-8"><input type="text" class="w3-white form-control" id="dateto" name="dateto"></div></div>
                <script type="text/javascript">
                $(function () {
                        $('#datefrom').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
                        $('#dateto').datepicker({format: 'yyyy-mm-dd', todayBtn: true, forceParse: false, keyboardNavigation: false});
                    });
                </script>
                <div class="row align-items-center my-auto"><div class="col-sm-4">Format:</div><div class="col-sm-8"><select name="format" class="w3-white form-control" id="format">
                                    <option label=""></option>
                                    <option label="Web Page">html</option>
                                    <option label="KML">kml</option>
                                    <option label="GeoJSON">json</option>
                                    <option label="CSV Spreadsheet">csv</option>
                                </select></div></div>
                <label for="download" class="download-label">
				<div class="row align-items-center my-auto"><div class="col-sm-4" data-toggle="tooltip" title="Download as a file instead of open in a browser window if you choose kml, csv or geojson.">
                    Download?</div><div class="col-sm-8"><input type="checkbox" id="download" name="download"></div></div></label>
                <div class="row align-items-center my-auto"><div class="col-sm-4">Results per page:</div><div class="col-sm-8"><input type="text" class="w3-white form-control" id="paging" name="paging"></div></div>
                
            </div>
            <div class="col-xl-2 col-md-6 w3-light-grey filter-div">
                <p class="h3 m-2 mb-3 text-center">Map Area Search</p>
                <p>Get only places within a shape drawn on the map.</p>
                <p>Use the tools on the map or manually enter values.</p>
                <select id="mapselector" class="h3 m-0 mt-2 mb-3 text-center">
                    <option id="bboxoption" value="bboxoption">Bounding Box</option>
                    <option id="polygonoption" value="polygonoption">Polygon</option>
                    <option id="circleoption" value="circleoption">Circle</option>  
                </select>
                <div id="bboxdiv">
                        <input type="hidden" id="bbox" name="bbox" value="">

                        <p class="text-center mb-0">Longitude</p>
                        <div class="rTableRow d-inline-flex" style="line-height:32px;">
                            <input type="text" class="w3-white form-control p-2" id="minlong" placeholder="min long"><p class="mr-2 ml-2 text-decoration-none">to</p><input type="text" class="w3-white form-control p-2" id="maxlong" placeholder="max long">
                        </div>

                        <p class="text-center mb-0">Latitude</p>
                        <div class="rTableRow d-inline-flex" style="line-height:32px;">
                            <input type="text" class="w3-white form-control p-2" id="minlat" placeholder="min lat"><p class="mr-2 ml-2 text-decoration-none">to</p><input type="text" class="w3-white form-control p-2" id="maxlat" placeholder="max lat">
                        </div>

                    </div>

                    <div id="polygondiv" class="hidden">
                        <input type="hidden" id="polygon" name="polygon" value="">
                        <div class="rTableRow d-inline-flex" style="line-height:32px;">
                            Points<input type="text" class="w3-white form-control p-2 ml-2" id="polygoninput" placeholder="0 0, 0 100, 100 100, 100 0, 0 0">
                        </div>
                    </div>

                    <div id="circlediv" class="hidden">
                        <input type="hidden" id="circle" name="circle" value="">
                        <div class="rTableRow d-inline-flex" style="line-height:32px;">
                            Centre<input type="text" class="w3-white form-control p-2 ml-2" id="circlelong" placeholder="longitude"><input type="text" class="w3-white form-control p-2 ml-2" id="circlelat" placeholder="latitude">
                        </div>
                        <div class="rTableRow d-inline-flex" style="line-height:32px;">
                            Radius(m)<input type="text" class="w3-white form-control p-2 ml-2" id="circlerad" placeholder="Radius in metres">
                        </div>
                    </div>
                    <button class="btn btn-secondary mt-3" id="mapdraw" type="button">Draw</button>
            </div>
            <div class="col-xl-6 col-md-12 filter-div">
                <!-- Leaflet Map -->
                <div id="ausmap"></div>
            </div>
        </div>
        <input type="hidden" id="names" name="names">
        <input type="hidden" id="fuzzynames" name="fuzzynames">
        <input type="hidden" id="containsnames" name="containsnames">
    </form>

    <div class="row">
        <!-- Bulk search placenames from file -->
        <div class="col-xl-5 col-md-6 offset-xl-1 sub-filter-div">
            
            <h3>Search placenames by file</h3>
                <!-- help hover button -->
                <p>Select the file to upload <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                                title="File must contain placenames in either LINE SEPARATED or COMMA SEPARATED format<br>
                                eg:<br><br>
                                newcastle<br>
                                cessnock<br>
                                shoal bay<br><br>
                                OR<br><br>
                                newcastle,cessnock,shoal bay"> 
                            </span></p>
            <p><strong>Search box will be ignored, other filters will be applied</strong></p>
            <div class="d-inline-flex justify-content-center">
                <form method="POST" action="something">@csrf<input type="file" name="bulkfileinput" id="bulkfileinput" class="d-inline-block"></form>
                <button type="button" class="btn btn-danger" id="bulkfileCancel" hidden>&times;</button>
            </div>
        </div>

        <!-- Search KML polygon from file -->
        <div class="col-xl-5 col-md-6 sub-filter-div">
            <h3>Search within a KML polygon</h3>
            <!-- MODAL Search within kml polygon from file -->
            <button type="button" class="mt-3 mb-3 btn btn-secondary" data-toggle="modal" data-target="#kmlPolygonSearchModal">Upload KML to search within polygon</button>
            <!-- MODAL popup -->
            <div class="modal fade" id="kmlPolygonSearchModal" tabindex="-1" role="dialog" aria-labelledby="kmlPolygonSearchModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 class="modal-title" id="kmlPolygonSearchModalLabel">Search within a polygon using a kml file</h3>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="kmlPolygonFileUploadForm">
                                <?php
                                    echo Form::open(array('url' => '/kmlpolygonsearch','files'=>'true'));
                                    echo 'Select the file to upload.';
                                ?>
                                <!-- help hover button -->
                                <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                                    title="File must be valid kml format and contain at least 1 Polygon tag"> 
                                </span>
                                <?php
                                    echo Form::file('polygonkml',['accept'=>'.kml']);
                                ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <?php echo Form::submit('Upload File');
                            echo Form::close(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
</div>

<!-- TLCMap js file for the leaflet map -->
<script type="text/javascript" src="{{ asset('/js/mappicker.js') }}" ></script>
