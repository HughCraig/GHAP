<!-- https://jqueryui.com/autocomplete/ -->
<script>
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



<div class="searchForm">
    <form action="search" autocomplete="off" method="GET" role="search" id="searchForm" name="searchForm" autocomplete="off">
            
        <!-- Search bar -->
        <div class="input-group">
            <input class="w3-white form-control" style="width:30%" type="text" name="fuzzyname" id="input" placeholder="Enter place name.">
            <button class="btn btn-primary" type="button" id="gazformsubmitbutton" onclick="submitSearchForm();">
                Search <i class="fa fa-search"></i>
            </button>
            
            <!-- placename or anps id selector-->
            <div>
                <select class="form-control-lg" id="input-select-box" onchange="changeInput();">
                    <option value="name" selected="selected">name</option>
                    <option value="anps_id">anps_id</option>
                </select>
            </div>
        </div>

        <!-- Exact match and Download check buttons -->
        <div class="row">
            <div class="col-sm-2">
                <label id="exact-match-div" class="mb-2">Exact Match? <input type="checkbox" id="exact-match" onchange="exactSearch();"></label>
                <label class="mb-2">Download? <input type="checkbox" id="download" name="download"></label>
            </div>
            <div class="col-sm-2">
                <label class="mb-2">Official <a href="https://www.anps.org.au/">ANPS</a> Gazetteer <input type="checkbox" id="searchausgaz" name="searchausgaz" checked></label>
                <label class="mb-2">TLCMap User Contributed Places <input type="checkbox" id="searchpublicdatasets" name="searchpublicdatasets" checked></label>
            </div>
        </div>
        <!-- Filters, now as cards instead of dropdown -->
        <div class="card-group">
            <!-- Left Filters card (General) -->
            <div id="filters_a" class="p-1 border-right-0 w3-light-grey card" style="max-width: 35rem;">
                <div class="rTables">
                    <p class="h3 m-2 mb-3 text-center">Filters</p>
                    <div class="rTableRow">
                        <div class="rTableCell">LGA: </div>
                        <div class="rTableCell"><input type="text" class="w3-white form-control" name="lga" id="lga" autocomplete="off"></div>
                    </div>
                    <div class="rTableRow">
                        <div class="rTableCell">Parish: </div>
                        <div class="rTableCell"><input type="text" class="w3-white form-control" name="parish" id="parish" autocomplete="off"></div>
                    </div>
                    <div class="rTableRow">
                        <div class="rTableCell">Feature Term: </div>
                        <div class="rTableCell"><input type="text" class="w3-white form-control" name="feature_term" id="feature_term" autocomplete="off"></div>
                    </div>
                    <div class="rTableRow">
                        <div class="rTableCell">State: </div>
                        <div class="rTableCell smallerinputs"><select class="w3-white form-control" name="state" id="state">
                            @foreach($states as $state)
                            <option label="{{$state->state_code}}">{{$state->state_code}}</option>
                            @endforeach
                        </select></div>
                    </div>
                    <div class="rTableRow"><div class="rTableCell">From ID:</div><div class="rTableCell smallerinputs"><input type="text" class="smallerinputs w3-white form-control" id="from" name="from"></div></div>
                    <div class="rTableRow"><div class="rTableCell">To ID: </div><div class="rTableCell smallerinputs"><input type="text" class="w3-white form-control" id="to" name="to"></div></div>
                    <div class="rTableRow"><div class="rTableCell">Format: </div>
                        <div class="rTableCell smallerinputs">
                            <select name="format" class="w3-white form-control" id="format">
                                <option label=""></option>
                                <option label="html">html</option>
                                <option label="kml">kml</option>
                                <option label="json">json</option>
                                <option label="csv">csv</option>
                            </select>
                        </div>
                    </div>
                    <div class="rTableRow"><div class="rTableCell">Results per Page:</div><div class="rTableCell smallerinputs"><input type="text" class="w3-white form-control" id="paging" name="paging"></div></div>
                </div>
            </div>
            <!-- Middle Filters card (bounding box) -->
            <div id="filters_b" class="p-1 pr-4 border-left-0 border-right-0 w3-light-grey card" style="max-width: 24rem;">
                <p class="h3 m-0 mt-2 mb-3 text-center">Map Area Search</p>
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
            <!-- Right Filters card (bounding box) -->
            <div id="filters_c" class="p-1 border-left-0 w3-light-grey card" style="max-width: 75rem;">
                <div id="ausmap"></div> <!-- leaflet map widget -->
            </div>
        </div>
    </form>
    <br>

    <!-- MODAL Bulk search from file button -->
	<p>Bulk search for placenames from a .txt or .csv file. The placenames may be comma separated on a single line or one per line:</p>
    <button type="button" class="mt-3 mb-3 btn btn-secondary" data-toggle="modal" data-target="#bulksearchModal">Bulk search for placenames from file</button>
    <!-- MODAL popup -->
    <div class="modal fade" id="bulksearchModal" tabindex="-1" role="dialog" aria-labelledby="bulksearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="bulksearchModalLabel">Search placenames by file</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="fileUploadForm">
                        <?php
                            echo Form::open(array('url' => '/file','files'=>'true'));
                            echo 'Select the file to upload.';
                        ?>
                        <!-- help hover button -->
                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" 
                            title="File must contain placenames in either LINE SEPARATED or COMMA SEPARATED format<br>
                            eg:<br><br>
                            newcastle<br>
                            cessnock<br>
                            shoal bay<br><br>
                            OR<br><br>
                            newcastle,cessnock,shoal bay"> 
                        </span>
                        <?php
                            echo Form::file('bulkfile',['accept'=>'.txt , .csv']);
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

    <!-- MODAL Search within kml polygon from file -->
	<p>Search for places within a polygon in a KML file. (Polygons may be exported from other systems as a KML file.) NB: this has a known bug to be fixed in the next update - it may work if coordinates of the polygon are each on a new line.</p>
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

<!-- TLCMap js file for the leaflet map -->
<script type="text/javascript" src="{{ asset('/js/mappicker.js') }}" ></script>