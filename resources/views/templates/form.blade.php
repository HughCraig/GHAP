@push('styles')
    <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/map-picker.css') }}">
    <style>
        .footer{
            display: none;
        }

        div.header {
            border-bottom: 3px solid black;
        }

        #mainsearchdiv {
            margin: 1% 0;
            display: flex;
            padding: 0 5%;
        }

        .tlcmapcontainer {
            margin-left: 0;
            margin-right: 0;
            margin-bottom: 0;
        }
        .w3-container {
            padding: 0;
        }
        .footer{
            display: none;
        }

        .secondary-nav{
            margin-bottom: 0;
        }

    </style>
@endpush

@push('scripts')
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        var bulkfileparser = "{{url('bulkfileparser')}}";
        var lgas = {!! $lgas !!};
        var parishes = {!! $parishes !!};
        var feature_terms = {!! $feature_terms !!};
        var layers = {!! $layers !!};
        var recordTypeMap = {!! $recordTypeMap !!}
        var userLayers =  @json($userLayers);
        var bboxscan = "{{ url('bboxscan') }}";
        var ajaxsearchdataitems = "{{ url('ajaxsearchdataitems') }}";
        var show_help_video_first_landing = "{{ config('app.show_help_video_first_landing') }}";
        var viewsRootUrl = "{{ config('app.views_root_url') }}";
        var viewsTemporalEarthUrl = "{{ config('app.views_temporal_earth_url') }}";
        var baseUrl = "{{ url('/') }}/"; 

        var home_page_places_shown = "{{ config('app.home_page_places_shown') }}";
        var isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
        var userName = @json(Auth::check() ? Auth::user()->name : null);
    </script>
    <!-- js-cookie library for cookie usages -->
    <script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
    <script src="{{ asset('/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/searchform.js') }}"></script>
    <script src="{{ asset('js/message-banner.js') }}"></script>
    <script src="{{ asset('js/validation.js') }}"></script>

    <script type="text/javascript" src="{{ asset('/js/form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/home.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/map-picker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/tlcmap.js') }}"></script>

@endpush

<!-- whole search and filter form -->
<div class="searchForm">

    <input type="hidden" id="csrfToken" value="{{ csrf_token() }}">
    <!-- Modal Add to dataset button -->
    @auth
    @include('modals.adddataitemmodal')
    @endauth

    <div id="searchForm">

        <!-- Search bar -->
        <div id="mainsearchdiv">

            <div class="d-flex justify-content-between" style="width: 100%; align-items:baseline">

                <div class="d-flex">
                    <div class="col-sm-auto">
                        <input type="text" class="form-control" name="fuzzyname" id="input" placeholder="Enter search">
                    </div>

                    <div class="col-sm-auto pt-2">
                        <a id="advancedSearchButton" href="#advancedaccordion" data-toggle="collapse">Advanced Search <i class="fa fa-chevron-down"></i></a>
                    </div>

                    <div class="col-sm-auto">
                        <button class="btn btn-primary" type="button" id="searchbutton">
                            Search <i class="fa fa-search"></i>
                        </button>
                    </div>


                    <button class="btn" type="button" id="resetbutton">
                        Reset
                    </button>
                </div>

                <div class="d-flex" style="align-items: center;">

                    <div class="d-flex view-button" style="align-items: baseline;">
                        <label class="radio" id="radio-map">
                            <input type="radio" name="typeFilter" class="typeFilter-map" checked>
                            <span class="label-body pl-1">Map</span>
                        </label>
                        <label class="radio" id="radio-list" style="padding-left: 3rem;">
                            <input type="radio" name="typeFilter" class="typeFilter-cluster">
                            <span class="label-body pl-1">Cluster</span>
                        </label>
                        <label class="radio" id="radio-list" style="padding-left: 3rem; padding-right: 2rem;">
                            <input type="radio" name="typeFilter" class="typeFilter-list">
                            <span class="label-body pl-1">List</span>
                        </label>
                    </div>

                    <select class="w3-white form-control num-places" style="height: 100%;" id="num-places">
                        <option value="100">100 places</option>
                        <option value="200">200 places</option>
                        <option value="500">500 places</option>
                        <option value="2000">2000 places</option>
                        <option value="ALL">All places</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Advanced Search and Filter -->
        <div id="advancedaccordion" class="collapse">
            <div class="d-flex justify-content-center w3-light-grey pt-4">
                <div class="row">
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
                </div>

            </div>
            <div class="d-flex justify-content-center w3-light-grey pb-4">
                <!-- Filters -->
                <div class="row">
                    <div class="col-lg-4 filter-div">
                        <p class="h3">
                            Filters
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" style="font-size:16px" title="Some records may not be comprehensively tagged. Tags for records may differ between states.">
                            </span>
                        </p>
                        <div class="row align-items-center my-auto mb-1">
                            <div class="col-sm-6" data-toggle="tooltip">
                                Search Description
                            </div>
                            <div class="col-sm-6">
                                <input type="checkbox" id="searchdescription" name="searchdescription">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6">Place Type:</div>
                            <div class="col-sm-6">
                                <select class="w3-white form-control" name="recordtype" id="recordtype">
                                    <option label="" selected></option>
                                    @foreach($recordtypes as $recordtype)
                                    <option label="{{$recordtype->type}}">{{$recordtype->type}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6">Layers:</div>
                            <div class="col-sm-6">
                                <input type="text" class="w3-white form-control" id="searchlayers" autocomplete="off">
                                <input type="hidden" name="searchlayers" id="selected-layers">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6" data-toggle="tooltip" title="This enables nuanced search and map creation within layers and needs special sytax, see under 'Search' in the GHAP Guide.">
                                <a href="https://tlcmap.org/help/guides/ghap-guide/" style="color: #000000; text-decoration: none;" target="_blank">Extended Data?</a>
                            </div>
                            <div class="col-sm-6">
                                <input type="text" class="w3-white form-control" name="extended_data" id="extended_data" autocomplete="off">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto" data-toggle="tooltip" title="Local Government Area.">
                            <div class="col-sm-6">LGA:</div>
                            <div class="col-sm-6">
                                <input type="text" class="w3-white form-control" name="lga" id="lga" autocomplete="off">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6">State/Territory:</div>
                            <div class="col-sm-6">
                                <select class="w3-white form-control" name="state" id="state">
                                    <option label="" selected></option>
                                    @foreach($states as $state)
                                    <option label="{{$state}}">{{$state}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6">Parish:</div>
                            <div class="col-sm-6">
                                <input type="text" class="w3-white form-control" name="parish" id="parish" autocomplete="off">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6" data-toggle="tooltip" title="Not all places are tagged with their feature for all states, so this will return only partial results for some areas.">
                                Feature:
                            </div>
                            <div class="col-sm-6">
                                <input type="text" class="w3-white form-control" name="feature_term" id="feature_term" autocomplete="off">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6">From ID:</div>
                            <div class="col-sm-6">
                                <input type="text" class="smallerinputs w3-white form-control" id="from" name="from">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6">To ID:</div>
                            <div class="col-sm-6">
                                <input type="text" class="w3-white form-control" id="to" name="to">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto" data-toggle="tooltip" title="Places without dates associated are not included.">
                            <div class="col-sm-6">Date From:</div>
                            <div class="col-sm-6">
                                <input type="text" class="smallerinputs w3-white form-control" id="datefrom" name="datefrom">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6">Date To:</div>
                            <div class="col-sm-6">
                                <input type="text" class="w3-white form-control" id="dateto" name="dateto">
                            </div>
                        </div>
                        <div class="row align-items-center my-auto">
                            <div class="col-sm-6">Format:</div>
                            <div class="col-sm-6">
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
                            <div class="col-sm-6" data-toggle="tooltip" title="Download as a file instead of open in a browser window if you choose kml, csv or geojson.">
                                Download?
                            </div>
                            <div class="col-sm-6">
                                <input type="checkbox" id="download" name="download">
                            </div>
                        </div>
                    </div>

                    <!-- Map Area Search -->
                    <div class="col-lg-4">
                        <p class="h3">
                            Specify map area
                            <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" style="font-size:16px" title="Pick a shape to draw on the map, or enter coordinates.">
                            </span>
                        </p>
                        <select id="mapselector" class="h4 m-0 mt-2 mb-3 text-center">
                            <option id="bboxoption" value="bboxoption">Bounding Box</option>
                            <option id="polygonoption" value="polygonoption">Polygon</option>

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

                        <button class="btn btn-primary mt-3" id="mapdraw" type="button">Draw</button>
                    </div>
                    <!-- End map Area Search -->

                    <div class="col-lg-4">
                        <!-- Bulk search placenames from file -->
                        <div class="bulk-placename-search">
                            <h3>
                                Search for a list of place names
                                <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" style="font-size:16px" title="Upload a file containing a list of place names, either one per line or separated by commas.">
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
                                <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" style="font-size:16px" title="Upload a KML file specifying a polygon.">
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
    </div>

    <!-- Map Area Display -->
    <div class="map-view">
        <div id="viewDiv" style="height: 820px; border-top: 3px solid black">
        </div>
    </div>

    <!-- END Map Area Display -->

    <!-- List Area Display -->
    <div class="list-view" style="border-top: 3px solid black; display:none ;">


        <div style="margin: 0 5%;">

            <h2>Search Results</h2>

            <div id="list-buttons">
                <!-- Export/Download -->
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle tlcmgreen" type="button" id="downloadDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Download
                    </button>
                    <div class="dropdown-menu" aria-labelledby="downloadDropdown">
                        <a class="dropdown-item grab-hover" id="downloadKml" href="#">KML</a>
                        <a class="dropdown-item grab-hover" id="downloadCsv" href="#">CSV</a>
                        <a class="dropdown-item grab-hover" id="downloadGeoJson" href="#">GeoJSON</a>
                        <a class="dropdown-item grab-hover" id="downloadRoCrate" href="#">RO-Crate</a>
                    </div>
                </div>

                <!-- Web Services Feed -->
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle tlcmgreen" type="button" id="wsfeedDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        WS Feed
                    </button>
                    <div class="dropdown-menu" aria-labelledby="wsfeedDropdown">
                        <a class="dropdown-item grab-hover" id="wsFeedKml" href="#">KML</a>
                        <a class="dropdown-item grab-hover" id="wsFeedCsv" href="#">CSV</a>
                        <a class="dropdown-item grab-hover" id="wsFeedGeoJson" href="#">GeoJSON</a>
                    </div>
                </div>

                @if (!empty(config('app.views_root_url')))
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        &#x1F30F View Maps...
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item grab-hover" id="view3d" href="#">3D Viewer</a>
                        <a class="dropdown-item grab-hover" id="viewCluster" href="#">Cluster</a>
                        <a class="dropdown-item grab-hover" id="viewJourney" href="#">Journey Route</a>
                        <a class="dropdown-item grab-hover" id="viewWerekata" href="#">Werekata Flight by Route</a>
                    </div>
                </div>
                @endif
            </div>

            <div class="mt-4 mb-1">
                <p>Note: Layers are contributed from many sources by many people or derived by computer
                    and are the responsibility of the contributor.
                    Layers may be incomplete and locations and dates may be imprecise.
                    Check the layer for details about the source. Absence in TLCMap does not indicate absence in reality.
                    Use of TLCMap may inform heritage research but is not a substitute for established formal and legal processes and consultation.</p>
            </div>

            <!-- save search -->
            <div id="list-save-search">
                @guest
                <div class="mb-2 form-group row">
                    <div class="col-xs-4">
                        <div class="mb-3 p-3 w3-pale-red"><a href="{{url('/login')}}">Log in</a> to save searches and contribute layers.</div>
                    </div>
                </div>
                @else
                <div class="mb-2 form-group row">
                    <div class="col-xs-4">
                        @include('modals.savesearchmodal')
                        <div class="mt-3 mb-3 p-3 w3-pale-green" id="save_search_message" style="display:none">Successfully added this search to your <a href="{{url('/myprofile/mysearches')}}">saved searches</a>!</div>
                    </div>

                </div>
                @endguest
            </div>

            <div class="form-group row">
                <div class="col-xs-4">
                    <div class="p-3 w3-pale-blue" id="display_info">
                    </div>
                </div>
            </div>
        </div>

        <div class="place-list pt-4" style="margin: 0 6%;">
        </div>

    </div>
    <!-- END List Area Display -->

    <!-- MODAL popup -->
    <!-- NB: this is the pop up content for a button above that opens it. This content needs to be place here outside of the main form, because it contains a form element
        and you'd get bad form nesting that breaks everything if you put it up with the button.
        I'm not sure how the KML polygon actually works and if it needs to be a form, etc. May be a candidate for refactor. At the moment with no time, it works so leave it.
        -->
    <div class="modal fade" id="kmlPolygonSearchModal" tabindex="-1" role="dialog" aria-labelledby="kmlPolygonSearchModalLabel" aria-hidden="true">
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