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
    <!-- js-cookie library for cookie usages -->
    <script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
    <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/searchform.js') }}"></script>
    <!-- TLCMap js file for the leaflet map -->
    <script type="text/javascript" src="{{ asset('/js/mappicker.js') }}"></script>
@endpush

<!-- whole search and filter form -->
<div class="searchForm">
    {{--
        The CSRF token is only used for the bulk search file upload, and it's retrieved by the frontend JS.
        So put the CSRF token outside the form to prevent the token appearing in the URL query string.
    --}}
    @csrf
    <form action="search" autocomplete="off" method="GET" role="search" id="searchForm" name="searchForm" enctype="multipart/form-data">
        <!-- Search bar -->
        <div id="mainsearchdiv" class="d-flex justify-content-center flex-fill mb-4">

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
                <div class="col-smauto pt-2">
                    @foreach ($datasources as $datasource)
                        <label data-toggle="tooltip" title="{{ $datasource->description }}">
                            {{ $datasource->name }}
                            <input type="checkbox" id="{{ $datasource->search_param_name }}" name="{{ $datasource->search_param_name }}" checked>
                        </label>
                    @endforeach
                </div>
                <div class="col-sm-auto">
                    <button class="btn btn-primary" type="button" id="gazformbutton" onclick="submitSearchForm();">
                        Search <i class="fa fa-search"></i>
                    </button>
                </div>
                <div class="col-sm-auto pt-2 ml-lg-5">
                    <a id="advancedSearchButton" href="#advancedaccordion" data-toggle="collapse">Advanced Search <i class="fa fa-chevron-down"></i></a>
                </div>
            </div>
        </div>


        <!-- Advanced Search and Filter -->
        <div id="advancedaccordion" class="collapse">
            <div class="d-flex justify-content-center w3-light-grey p-4">
                <!-- Filters -->
                <div class="row">
                    <div class="col-lg-4 filter-div">
                        <p class="h3">
                            Filters
                            <span tabindex="0" data-html="true"
                                  data-animation="true"
                                  class="glyphicon glyphicon-question-sign"
                                  data-toggle="tooltip"
                                  data-placement="right"
                                  style="font-size:16px"
                                  title="Some records may not be comprehensively tagged. Tags for records may differ between states.">
                            </span>
                        </p>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-4">Place Type:</div>
                            <div class="col-sm-8">
                                <select class="w3-white form-control" name="recordtype" id="recordtype">
                                    <option label="" selected></option>
                                    @foreach($recordtypes as $recordtype)
                                        <option label="{{$recordtype->type}}">{{$recordtype->type}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
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
                                        <option label="{{$state}}">{{$state}}</option>
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
                        <div class="row align-items-center my-auto mb-1">
                            <div class="col-sm-4" data-toggle="tooltip">
                                Search within Description field?
                            </div>
                            <div class="col-sm-8">
                                <input type="checkbox" id="searchdescription" name="searchdescription">
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

                    <!-- Map Area Search -->
                    <div class="col-lg-4">
                        <p class="h3">
                            Specify map area
                            <span tabindex="0" data-html="true"
                                  data-animation="true"
                                  class="glyphicon glyphicon-question-sign"
                                  data-toggle="tooltip"
                                  data-placement="right"
                                  style="font-size:16px"
                                  title="Pick a shape to draw on the map, or enter coordinates.">
                            </span>
                        </p>
                        <select id="mapselector" class="h4 m-0 mt-2 mb-3 text-center">
                            <option id="bboxoption" value="bboxoption">Bounding Box</option>
                            <option id="polygonoption" value="polygonoption">Polygon</option>
                            <option id="circleoption" value="circleoption">Circle</option>
                        </select>
                        <div id="bboxdiv">
                            <input type="hidden" id="bbox" name="bbox" value="">

                            <p class="mb-0">Longitude</p>
                            <div class="rTableRow d-inline-flex" style="line-height:32px;">
                                <input type="text" class="w3-white form-control p-2" id="minlong" placeholder="min long">
                                <p class="mr-2 ml-2 text-decoration-none">to</p>
                                <input type="text" class="w3-white form-control p-2" id="maxlong" placeholder="max long">
                            </div>

                            <p class="mb-0">Latitude</p>
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
                                Centre&nbsp;Lng:
                                <input type="text" class="w3-white form-control p-2 ml-2" id="circlelong" placeholder="longitude">
                            </div>
                            <div></div>
                            <div class="rTableRow d-inline-flex" style="line-height:32px;">
                                Lat:
                                <input type="text" class="w3-white form-control p-2 ml-2" id="circlelat" placeholder="latitude">
                            </div>
                            <div></div>
                            <div class="rTableRow d-inline-flex" style="line-height:32px;">
                                Radius(m)
                                <input type="text" class="w3-white form-control p-2 ml-2" id="circlerad" placeholder="Radius in metres">
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" id="mapdraw" type="button">Draw</button>
                    </div>
                    <!-- End map Area Search -->

                    <div class="col-lg-4">
                        <!-- Bulk search placenames from file -->
                        <div class="bulk-placename-search">
                            <h3>
                                Search for a list of place names
                                <span tabindex="0" data-html="true"
                                      data-animation="true"
                                      class="glyphicon glyphicon-question-sign"
                                      data-toggle="tooltip"
                                      data-placement="right"
                                      style="font-size:16px"
                                      title="Upload a file containing a list of place names, either one per line or separated by commas.">
                                </span>
                            </h3>

                            <div class="d-inline-flex justify-content-center">
                                <input type="file" name="bulkfileinput" id="bulkfileinput" class="d-inline-block pl-0">
                                <button type="button" class="btn btn-danger" id="bulkfileCancel" hidden>&times;</button>
                            </div>
                        </div>
                        <!-- END Bulk search placenames from file -->

                        <!-- Search KML polygon from file -->
                        <div class="kml-search">
                            <h3>
                                Search within a KML polygon
                                <span tabindex="0" data-html="true"
                                      data-animation="true"
                                      class="glyphicon glyphicon-question-sign"
                                      data-toggle="tooltip"
                                      data-placement="right"
                                      style="font-size:16px"
                                      title="Upload a KML file specifying a polygon.">
                                </span>
                            </h3>
                            <!-- MODAL Search within kml polygon from file
                        NB: the popup content for this is below. It can't be here as it contains a form element, which would create bad form nesting. -->
                            <button type="button" class="d-inline-block border border-dark" data-toggle="modal" data-target="#kmlPolygonSearchModal">
                                Choose File
                            </button>
                        </div>
                        <!-- END KML polygon from file -->
                    </div>
                </div>
            </div>
        </div>
        <!-- END Advanced Search and Filter -->

        <!--  not sure why these hidden fields are here, but suspect there is some funk where js sets this according to user selection from drop down. -->
        <input type="hidden" id="names" name="names">
        <input type="hidden" id="fuzzynames" name="fuzzynames">
        <input type="hidden" id="containsnames" name="containsnames">
    </form>

    <!-- Map Area Search -->
    <div id="mapareadiv" class="d-flex flex-fill align-items-stretch mt-4">
        <div class="filter-div flex-fill" style="width:100%;height:100%;">
            <!-- Leaflet Map -->
            <div id="ausmap" class="flex-fill" style="height:65vh;margin-bottom:20px;min-width:300px;"></div>
        </div>
    </div>
    <!-- END Map Area Search -->

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
