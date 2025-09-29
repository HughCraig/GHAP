@push('styles')
    <link href="{{ asset('/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/map-picker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link href="{{ asset('/css/jquery.tagsinput.css') }}" rel="stylesheet">
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
    <script type="text/javascript" src="{{ asset('/js/jquery.tagsinput.js') }}"></script>

    <script type="text/javascript" src="{{ asset('/js/stmetrics-csv-download.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/dataitem.js') }}"></script> 
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
    @include('modals.addnewdatasetmodal')
    @endauth

    <div id="searchForm">

        <!-- Search bar -->
        <div id="mainsearchdiv">

            <div class="d-flex justify-content-between" style="width: 100%; align-items:baseline">

                <div class="d-flex">
                 
                    <div class="col-sm-auto pl-0 pr-0 mr-4">
                        <input type="text" class="form-control" name="fuzzyname" id="input" placeholder="Search places and culture">
                    </div>

                    <div class="col-sm-auto pt-2 d-flex justify-content-center pr-0">
                        <a id="advancedSearchButton" href="#advancedaccordion" data-bs-toggle="collapse"><i class="fa fa-chevron-down"></i></a>
                    </div>

                    <div class="col-sm-auto pl-0 pr-0 datasource-filter d-flex justify-content-center">
                        <select class="form-control" id="input-select-box">
                            <option value="containsname" selected="selected">Contains</option>
                            <option value="fuzzyname">Similar Match</option>
                            <option value="name">Exact Match</option>
                            <option value="id">Place ID</option>
                        </select>
                    </div>

                    <div class="col-sm-auto pl-0">
                        <button class="btn btn-primary" type="button" id="searchbutton">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>

                    <button class="btn" type="button" id="resetbutton">
                        Reset
                    </button>
                </div>

                <div class="d-flex">
                    <a id="featuredLayersButton" href="#featuredLayersAccordion" data-bs-toggle="collapse" style="color:white"><button class="btn btn-primary">Featured Layers<i class="fa fa-chevron-down pl-2"></i></button></a>
                </div>


                <div class="d-flex" style="align-items: center;">

                    <label data-bs-toggle="tooltip" title="" class="d-flex mb-0 mr-3 datasource-filter btn" style="background-color: orange;" data-original-title="Official Australian Placenames">
                        <div class="pl-1 pr-1">
                            ANPS Gazetteer
                        </div>
                        <input type="checkbox" id="searchausgaz" name="searchausgaz" style="margin-top: 2px; cursor:pointer" checked>
                    </label>
                    <label data-bs-toggle="tooltip" title="" class="d-flex mb-0 mr-3 datasource-filter btn" style="background-color: #FE6A1B;" data-original-title="Composite Gazetteer of Australia">
                        <div class="pl-1 pr-1">
                            NCG Gazetteer
                        </div>
                        <input type="checkbox" id="searchncg" name="searchncg" style="margin-top: 2px; cursor:pointer" checked>
                    </label>
                    <label data-bs-toggle="tooltip"  class="d-flex mb-0 mr-2 datasource-filter btn" style="background-color: #FFD580;" data-original-title="Contributed layers">
                        <div class="pl-1 pr-1">
                            Layers
                        </div>
                        <input type="checkbox" id="searchpublicdatasets" name="searchpublicdatasets" style="margin-top: 2px; cursor:pointer" checked >
                    </label>

                    <div class="d-flex view-button pl-5" style="align-items: baseline;">
                        <label class="radio" id="radio-map">
                            <input type="radio" name="typeFilter" class="typeFilter-map">
                            <span class="label-body pl-1">Points</span>
                        </label>
                        <label class="radio" id="radio-map" style="padding-left: 3rem;">
                            <input type="radio" name="typeFilter" class="typeFilter-cluster">
                            <span class="label-body pl-1">Cluster</span>
                        </label>
                        <label class="radio" id="radio-list" style="padding-left: 3rem; padding-right: 2rem;">
                            <input type="radio" name="typeFilter" class="typeFilter-list">
                            <span class="label-body pl-1">List</span>
                        </label>
                    </div>

                </div>
            </div>
        </div>

        <div id="featuredLayersAccordion" class="collapse">
            <div class="p-4">
                <div class="row justify-content-center">

                @foreach($featuredLayers as $featuredLayer)
                    <div class="col-3 mb-3 d-flex justify-content-center">
                        <button type="button"
                                class="featured-tile featuredLayerbutton"
                                data-featured_url="{{ $featuredLayer->featured_url }}">
                            <div class="thumb">
                                @if(!empty($featuredLayer->image_path))
                                    <img src="{{ asset('storage/images/' . $featuredLayer->image_path) }}"
                                        alt="{{ $featuredLayer->name }}">
                                @else
                                    <img src="{{ asset('img/tlcmap_main.png') }}">
                                @endif
                            </div>
                            <div class="label">{{ $featuredLayer->name }}</div>
                        </button>
                    </div>
                @endforeach

                @foreach($featuredmultilayers as $featuredmultilayer)
                    <div class="col-3 mb-3 d-flex justify-content-center">
                        <button type="button"
                                class="featured-tile featuredLayerbutton"
                                data-featured_url="{{ $featuredmultilayer->featured_url }}">
                            <div class="thumb">
                            @if(!empty($featuredmultilayer->image_path))
                                <img src="{{ asset('storage/images/' . $featuredmultilayer->image_path) }}"
                                    alt="{{ $featuredmultilayer->name }}">
                            @else
                                <img src="{{ asset('img/tlcmap_main.png') }}">
                            @endif
                            </div>
                            <div class="label">{{ $featuredmultilayer->name }}</div>
                        </button>
                    </div>
                @endforeach

                </div>
            </div>
        </div>

        <!-- Advanced Search and Filter -->
        <div id="advancedaccordion" class="collapse">
            <div class="d-flex justify-content-center w3-light-grey pb-4">
                <!-- Filters -->
                <div class="row" style="min-width: 75%;">

                    <div class="col-lg-6">
                        <div class="row">
                            <div class="col-lg-7">
                                <!-- All filter section -->
                                <p class="h4">
                                    Filters
                                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-bs-toggle="tooltip" data-placement="right" style="font-size:14px" title="Some records may not be comprehensively tagged. Tags for records may differ between states.">
                                    </span>
                                </p>

                                <div class="row align-items-center my-auto pb-4">
                                    <div class="col-sm-6">
                                        <select class="w3-white form-control" name="filterType" id="filterType">
                                            <option label="Place Type" selected>Place-Type</option>
                                            <option label="Layers">Layers</option>
                                            <option label="Extended Data">Extended-Data</option>
                                            <option label="LGA">LGA</option>
                                            <option label="State/Territory">State-Territory</option>
                                            <option label="Parish">Parish</option>
                                            <option label="Feature">Feature</option>
                                            <option label="From ID">From-ID</option>
                                            <option label="To ID">To-ID</option>
                                            <option label="Date From">Date-From</option>
                                            <option label="Date To">Date-To</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <button id="addFilter" class="btn btn-primary">Add</button>
                                    </div>
                                </div>

                                <div id="filtersContainer">
                                    <div class="row align-items-center my-auto" id="filter-Place-Type" style="display: none;">
                                        <div class="col-sm-6">Place Type:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <select class="w3-white form-control" name="recordtype" id="recordtype">
                                                <option label="" selected></option>
                                                @foreach($recordtypes as $recordtype)
                                                <option label="{{$recordtype->type}}">{{$recordtype->type}}</option>
                                                @endforeach
                                            </select>
                                            
                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-Layers" style="display: none;">
                                        <div class="col-sm-6">Layers:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="w3-white form-control" id="searchlayers" autocomplete="off">
                                            <input type="hidden" name="searchlayers" id="selected-layers">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-Extended-Data" style="display: none;">
                                        <div class="col-sm-6">
                                            <a href="{{ config('app.tlcmap_doc_url') }}/help/guides/guide/" style="color: #000000; text-decoration: none;" target="_blank" data-bs-toggle="tooltip" title="This enables nuanced search and map creation within layers and needs special syntax, see under 'Search' in the Guide.">Extended Data?</a>
                                        </div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="w3-white form-control" name="extended_data" id="extended_data" autocomplete="off">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-LGA" style="display: none;" data-bs-toggle="tooltip" title="Local Government Area.">
                                        <div class="col-sm-6">LGA:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="w3-white form-control" name="lga" id="lga" autocomplete="off">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-State-Territory" style="display: none;">
                                        <div class="col-sm-6">State/Territory:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <select class="w3-white form-control" name="state" id="state">
                                                <option label="" selected></option>
                                                @foreach($states as $state)
                                                <option label="{{$state}}">{{$state}}</option>
                                                @endforeach
                                            </select>

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-Parish" style="display: none;">
                                        <div class="col-sm-6">Parish:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="w3-white form-control" name="parish" id="parish" autocomplete="off">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-Feature" style="display: none;" data-bs-toggle="tooltip" title="Not all places are tagged with their feature for all states, so this will return only partial results for some areas.">
                                        <div class="col-sm-6">Feature:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="w3-white form-control" name="feature_term" id="feature_term" autocomplete="off">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-From-ID" style="display: none;">
                                        <div class="col-sm-6">From ID:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="smallerinputs w3-white form-control" id="from" name="from">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-To-ID" style="display: none;">
                                        <div class="col-sm-6">To ID:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="w3-white form-control" id="to" name="to">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-Date-From" style="display: none;" data-bs-toggle="tooltip" title="Places without dates associated are not included.">
                                        <div class="col-sm-6">Date From:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="smallerinputs w3-white form-control" id="datefrom" name="datefrom">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center my-auto" id="filter-Date-To" style="display: none;">
                                        <div class="col-sm-6">Date To:</div>
                                        <div class="col-sm-6 vertical-center">
                                            <input type="text" class="w3-white form-control" id="dateto" name="dateto">

                                            <span tabindex="0" class="glyphicon glyphicon-remove-sign remove-filter-button">
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5" style="margin-top:6%" id="home-description-numplaces">

                                <div class="row align-items-center my-auto mb-1">
                                    <div class="col-sm-8 pl-0" data-bs-toggle="tooltip">
                                        Search Description
                                        <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-bs-toggle="tooltip" data-placement="right" title="Also search Description field"></span>
                                    </div>
                                    <div class="col-sm-4">
                                        <input type="checkbox" id="searchdescription" name="searchdescription">
                                    </div>
                                </div>

                                <div class="row align-items-center my-auto mb-1 pt-4">
                                    <select class="w3-white form-control num-places pl-0" style="width:auto" id="num-places">
                                        <option value="100">100 places</option>
                                        <option value="200">200 places</option>
                                        <option value="500">500 places</option>
                                        <option value="2000">2000 places</option>
                                        <option value="5000">5000 places</option>
                                    </select>          
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6" style="border-left: 2px solid black;">
                        <!-- Map Area Search -->
                        <div class="col-lg-6">

                            <p class="h4">
                                Search within region
                                <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-bs-toggle="tooltip" data-placement="right" style="font-size:14px" title="Use the shapes at top right of the map to draw a region on the map, or provide details below.">
                                </span>
                            </p>

                            <div class="row mb-2">
                                <div class="col-sm-6">
                                    <select id="mapselector" class="h5 m-0 mt-2 mb-3 text-center">
                                        <option id="bboxoption" value="bboxoption">Bounding Box</option>
                                        <option id="polygonoption" value="polygonoption">Polygon</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <button class="btn btn-primary" id="mapdraw" type="button">Draw</button>
                                </div>
                            </div>

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

                        </div>
                        <!-- End map Area Search -->

                        <!-- file upload  -->
                        <div class="col-lg-6">
                            <!-- Bulk search placenames from file -->
                            <div class="bulk-placename-search">
                                <h4>
                                    Search for a list of place names
                                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-bs-toggle="tooltip" data-placement="right" style="font-size:14px" title="Upload a file containing a list of place names, either one per line or separated by commas.">
                                    </span>
                                </h4>


                                <input type="file" name="bulkfileinput" id="bulkfileinput" class="d-inline-block pl-0" style="font-size: 14px;">
                                <button type="button" class="btn btn-danger" id="bulkfileCancel" hidden>&times;</button>

                            </div>
                            <!-- END Bulk search placenames from file -->

                            <!-- Search KML polygon from file -->
                            <div class="kml-search">
                                <h4>
                                    Search within a KML polygon
                                    <span tabindex="0" data-html="true" data-animation="true" class="glyphicon glyphicon-question-sign" data-bs-toggle="tooltip" data-placement="right" style="font-size:14px" title="Upload a KML file specifying a polygon. No visualisation. Downloadable results only.">
                                    </span>
                                </h4>
                                <!-- MODAL Search within kml polygon from file
                                    NB: the popup content for this is below. It can't be here as it contains a form element, which would create bad form nesting. -->
                                <button type="button" class="d-inline-block border border-dark" data-bs-toggle="modal" data-bs-target="#kmlPolygonSearchModal" style="padding-top: 1%; padding-bottom:1%">
                                    Choose file
                                </button>
                            </div>
                            <!-- END KML polygon from file -->
                        </div>
                        <!-- End of file upload  -->
                    </div>

                </div>
            </div>
        </div>
        <!-- END Advanced Search and Filter -->


        <button id="scrollToTopButton" class="scroll-to-top" onclick="scrollToTopFunction()">↑</button>

        <!--  Used for bulk name search -->
        <input type="hidden" id="names" name="names">
        <input type="hidden" id="fuzzynames" name="fuzzynames">
        <input type="hidden" id="containsnames" name="containsnames">

        <input type="hidden" id="dataitemid" name="dataitemid">
        <input type="hidden" id="dataitemuid" name="dataitemuid">
    </div>

    <!-- Map Area Display -->
    <div class="map-view">
        <div id="viewDiv">
        </div>

        <div id="featuredLayerView" style="display: none;">
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
                    <button class="btn btn-secondary dropdown-toggle tlcmgreen" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Download
                    </button>
                    <div class="dropdown-menu" aria-labelledby="downloadDropdown">
                        <a class="dropdown-item grab-hover" id="downloadKml" href="#">KML</a>
                        <a class="dropdown-item grab-hover" id="downloadCsv" href="#">CSV</a>
                        <a class="dropdown-item grab-hover shown_in_search" id="downloadGeoJson" href="#">GeoJSON</a>
                        <!-- <a class="dropdown-item grab-hover shown_in_search" id="downloadRoCrate" href="#">RO-Crate</a> -->
                    </div>
                </div>

                <!-- Web Services Feed -->
                <div class="dropdown shown_in_search">
                    <button class="btn btn-secondary dropdown-toggle tlcmgreen" type="button" id="wsfeedDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        WS Feed
                    </button>
                    <div class="dropdown-menu" aria-labelledby="wsfeedDropdown">
                        <a class="dropdown-item grab-hover" id="wsFeedKml" href="#">KML</a>
                        <a class="dropdown-item grab-hover" id="wsFeedCsv" href="#">CSV</a>
                        <a class="dropdown-item grab-hover" id="wsFeedGeoJson" href="#">GeoJSON</a>
                    </div>
                </div>

                @if (!empty(config('app.views_root_url')))
                <div class="dropdown shown_in_search">
                    <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
            <div id="list-save-search" class="shown_in_search">
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

    <div id="loadingWheel">
        <div class="spinner"></div>
        <div class="loading-text"></div>
    </div>

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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="kmlPolygonFileUploadForm">
                        <div>File must be valid kml format and contain at least 1 Polygon tag</div>
                        <input type="file" id="polygonkml" name="polygonkml" accept=".kml">
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" value="Search" id="polygonkml_search">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- whole search and filter form -->