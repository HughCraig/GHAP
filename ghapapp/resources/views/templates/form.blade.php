@push('styles')
    <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        var bulkfileparser = "{{url('bulkfileparser')}}";
        var lgas = {!! $lgas !!};
        var parishes = {!! $parishes !!};
        var feature_terms = {!! $feature_terms !!};
    </script>
    <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/searchform.js') }}"></script>
    <!-- TLCMap js file for the leaflet map -->
    <script type="text/javascript" src="{{ asset('/js/mappicker.js') }}"></script>
@endpush

<!-- whole search and filter form -->
<div class="searchForm">

    <form action="search" autocomplete="off" method="GET" role="search" id="searchForm" name="searchForm" enctype="multipart/form-data">
        <!-- Search bar -->
        <div id="mainsearchdiv" class="d-flex justify-content-center flex-fill">

            <div class="row">
                <div class="col-sm-auto">
                    <input type="text" class="form-control" name="fuzzyname" id="input" placeholder="Enter search">
                </div>
                <div class="col-sm-auto">
                    <select class="form-control" id="input-select-box" onchange="changeInput(this);">
                        <option value="containsname" selected="selected">Contains</option>
                        <option value="fuzzyname">Fuzzy</option>
                        <option value="name">Exact Match</option>
                        <option value="anps_id">anps_id</option>
                    </select>
                </div>
                <div class="col-smauto">
                    <label data-toggle="tooltip" title="Search ANPS aggregated gazetteer of 'official' place names.">
                        Gazetteer
                        <input type="checkbox" id="searchausgaz" name="searchausgaz" checked>
                    </label>

                    <label data-toggle="tooltip" title="Search layers from research and user community.">
                        Layers
                        <input type="checkbox" id="searchpublicdatasets" name="searchpublicdatasets" checked>
                    </label>
                </div>
                <div class="col-sm-auto">
                    <button class="btn btn-primary" type="button" id="gazformbutton" onclick="submitSearchForm();">
                        Search <i class="fa fa-search"></i>
                    </button>
                </div>
                <div class="col-sm-auto">
                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign"
                          data-toggle="tooltip" data-placement="right"
                          style="font-size: 32px;"
                          title="Find results within the gazetteer and/or community contributed humanities information. Try 'contains', 'fuzzy' or 'exact'. Or use filters below, simply select an area on the map, or combine searches and filters. Max 50,000 results per page or file export.">
                    </span>
                </div>
            </div>
        </div>


        <!-- Advanced Search and Filter -->
        <div class="d-flex justify-content-center w3-light-grey ">
            <div class="row">
                <a href="#advancedaccordion" data-toggle="collapse">Advanced Search and Filter</a>
            </div>
        </div>
        <div id="advancedaccordion" class="collapse">
            <div class="d-flex justify-content-center w3-light-grey">

                <!-- Filters -->
                <div class="row">
                    <div class="col-sm-auto filter-div" style="max-width:500px; margin-bottom: 1em;">
                        <p class="h3">Filters</p>
                        <p>Note: this may find some but not necessarily all as records may not be tagged
                            comprehensively, and state records differ.</p>
                        <div class="row align-items-center my-auto" data-toggle="tooltip" title="Local Government Area.">
                            <div class="col-sm-4">LGA:</div>
                            <div class="col-sm-8">
                                <input type="text" class="w3-white form-control" name="lga" id="lga" autocomplete="off">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4">State:</div>
                            <div class="col-sm-8">
                                <select class="w3-white form-control" name="state" id="state">
                                    <option label="" selected></option>
                                    @foreach($states as $state)
                                        <option label="{{$state->state_code}}">{{$state->state_code}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4">Parish:</div>
                            <div class="col-sm-8">
                                <input type="text" class="w3-white form-control" name="parish" id="parish" autocomplete="off">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4" data-toggle="tooltip" title="Not all places are tagged with their feature for all states, so this will return only partial results for some areas.">
                                Feature:
                            </div>
                            <div class="col-sm-8">
                                <input type="text" class="w3-white form-control" name="feature_term" id="feature_term" autocomplete="off">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4">From ID:</div>
                            <div class="col-sm-8">
                                <input type="text" class="smallerinputs w3-white form-control" id="from" name="from">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4">To ID:</div>
                            <div class="col-sm-8">
                                <input type="text" class="w3-white form-control" id="to" name="to">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto" data-toggle="tooltip" title="Places without dates associated are not included.">
                            <div class="col-sm-4">Date From:</div>
                            <div class="col-sm-8">
                                <input type="text" class="smallerinputs w3-white form-control" id="datefrom" name="datefrom">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4">Date To:</div>
                            <div class="col-sm-8">
                                <input type="text" class="w3-white form-control" id="dateto" name="dateto">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4">Format:</div>
                            <div class="col-sm-8">
                                <select name="format" class="w3-white form-control" id="format">
                                    <option label=""></option>
                                    <option label="Web Page">html</option>
                                    <option label="KML">kml</option>
                                    <option label="GeoJSON">json</option>
                                    <option label="CSV Spreadsheet">csv</option>
                                </select>
                            </div>
                        </div>
                        <label for="download" class="download-label"></label>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4" data-toggle="tooltip" title="Download as a file instead of open in a browser window if you choose kml, csv or geojson.">
                                Download?
                            </div>
                            <div class="col-sm-8">
                                <input type="checkbox" id="download" name="download">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4">Results per page:</div>
                            <div class="col-sm-8">
                                <input type="text" class="w3-white form-control" id="paging" name="paging">
                            </div>
                        </div>
                    </div>

                    <!-- Bulk search placenames from file -->
                    <div class="col-sm-auto ">
                        <h3>Search for a list of place names</h3>
                        <!-- help hover button -->
                        <p>
                            Select a file containing a list of place names to upload
                            <span tabindex="0" data-html="true"
                                data-animation="true"
                                class="glyphicon glyphicon-question-sign"
                                data-toggle="tooltip"
                                data-placement="right"
                                title="File must have placenames either one per line or seperated by commas.">
                            </span>
                        </p>

                        <div class="d-inline-flex justify-content-center">
                            @csrf
                            <input type="file" name="bulkfileinput" id="bulkfileinput" class="d-inline-block ">
                            <button type="button" class="btn btn-danger" id="bulkfileCancel" hidden>&times;</button>
                        </div>
                    </div>
                    <!-- END Bulk search placenames from file -->


                    <!-- Search KML polygon from file -->
                    <div class="col-sm-auto ">
                        <h3>Search within a KML polygon</h3>
                        <!-- MODAL Search within kml polygon from file
                    NB: the popup content for this is below. It can't be here as it contains a form element, which would create bad form nesting. -->
                        <p>Upload KML file to search within polygon.</p>
                        <button type="button" class="d-inline-block border border-dark" data-toggle="modal" data-target="#kmlPolygonSearchModal">
                            Choose File
                        </button>
                    </div>
                    <!-- END KML polygon from file -->

                </div>
            </div>
        </div>
        <!-- END Advanced Search and Filter -->


        <!-- Map Area Search -->
        <div id="mapareadiv" class="d-flex flex-fill align-items-stretch">

            <div class="filter-div flex-fill" style="max-width: 320px; margin-right: 1em;">
                <p class="h3 m-2 mb-3 text-center">Map Area Search</p>
                <p>Pick a shape at the left of the map to draw an area to search in, or enter details:</p>
                <select id="mapselector" class="h4 m-0 mt-2 mb-3 text-center">
                    <option id="bboxoption" value="bboxoption">Bounding Box</option>
                    <option id="polygonoption" value="polygonoption">Polygon</option>
                    <option id="circleoption" value="circleoption">Circle</option>
                </select>
                <div id="bboxdiv">
                    <input type="hidden" id="bbox" name="bbox" value="">

                    <p class="text-center mb-0">Longitude</p>
                    <div class="rTableRow d-inline-flex" style="line-height:32px;">
                        <input type="text" class="w3-white form-control p-2" id="minlong" placeholder="min long">
                        <p class="mr-2 ml-2 text-decoration-none">to</p>
                        <input type="text" class="w3-white form-control p-2" id="maxlong" placeholder="max long">
                    </div>

                    <p class="text-center mb-0">Latitude</p>
                    <div class="rTableRow d-inline-flex" style="line-height:32px;">
                        <input type="text" class="w3-white form-control p-2" id="minlat" placeholder="min lat">
                        <p class="mr-2 ml-2 text-decoration-none">to</p>
                        <input type="text" class="w3-white form-control p-2" id="maxlat" placeholder="max lat">
                    </div>

                </div>

                <div id="polygondiv" class="hidden">
                    <input type="hidden" id="polygon" name="polygon" value="">
                    <div class="rTableRow d-inline-flex" style="line-height:32px;">
                        Points
                        <input type="text" class="w3-white form-control p-2 ml-2" id="polygoninput" placeholder="0 0, 0 100, 100 100, 100 0, 0 0">
                    </div>
                </div>

                <div id="circlediv" class="hidden">
                    <input type="hidden" id="circle" name="circle" value="">
                    <div class="rTableRow d-inline-flex" style="line-height:32px;">
                        Centre
                    </div>
                    <div class="rTableRow d-inline-flex" style="line-height:32px;">
                        Lng:
                        <input type="text" class="w3-white form-control p-2 ml-2" id="circlelong" placeholder="longitude">
                    </div>
                    <div class="rTableRow d-inline-flex" style="line-height:32px;">
                        Lat:
                        <input type="text" class="w3-white form-control p-2 ml-2" id="circlelat" placeholder="latitude">
                    </div>
                    <div class="rTableRow d-inline-flex" style="line-height:32px;">
                        Radius(m)
                        <input type="text" class="w3-white form-control p-2 ml-2" id="circlerad" placeholder="Radius in metres">
                    </div>
                </div>
                <button class="btn btn-secondary mt-3" id="mapdraw" type="button">Draw</button>
            </div>


            <div class="filter-div flex-fill" style="width=100%;height:100%;">
                <!-- Leaflet Map -->
                <div id="ausmap" class="flex-fill" style="height:65vh;margin-bottom:20px;min-width:300px;"></div>
            </div>
        </div>
        <!-- END Map Area Search -->

        <!--  not sure why these hidden fields are here, but suspect there is some funk where js sets this according to user selection from drop down. -->
        <input type="hidden" id="names" name="names">
        <input type="hidden" id="fuzzynames" name="fuzzynames">
        <input type="hidden" id="containsnames" name="containsnames">
    </form>


    <!-- MODAL popup -->
    <!-- NB: this is the pop up content for a button above that opens it. This content needs to be place here outside of the main form, because it contains a form element
    and you'd get bad form nesting that breaks everything if you put it up with the button.
    I'm not sure how the KML polygon actually works and if it needs to be a form, etc. May be a candidate for refactor. At the moment with no time, it works so leave it.
    -->
    <div class="modal fade" id="kmlPolygonSearchModal" tabindex="-1" role="dialog"
         aria-labelledby="kmlPolygonSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="kmlPolygonSearchModalLabel">Search within a polygon using a KML file. Eg:
                        you may have a KML file of an LGA suburb or national park.</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="kmlPolygonFileUploadForm">
                        <?php
                        echo Form::open(array('url' => '/kmlpolygonsearch', 'files' => 'true'));
                        echo 'File must be valid kml format and contain at least 1 Polygon tag.';
                        echo Form::file('polygonkml', ['accept' => '.kml']);
                        ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php echo Form::submit('Search');
                    echo Form::close(); ?>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- whole search and filter form -->
