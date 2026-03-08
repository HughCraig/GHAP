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
                 
                    <div class="col-sm-auto ps-0 pe-0 me-4">
                        <input type="text" class="form-control" name="fuzzyname" id="input" placeholder="Search places and culture">
                    </div>

                    <div class="d-flex align-items-center justify-content-center pe-0">
                        <a id="advancedSearchButton" href="#advancedaccordion" data-bs-toggle="collapse" class="text-black mx-2"><i class="fa fa-chevron-down"></i></a>
                    </div>

                    <div class="col-sm-auto ps-0 pe-0 datasource-filter d-flex justify-content-center">
                        <select class="form-control" id="input-select-box">
                            <option value="containsname" selected="selected">Contains</option>
                            <option value="fuzzyname">Similar Match</option>
                            <option value="name">Exact Match</option>
                            <option value="id">Place ID</option>
                        </select>
                    </div>

                    <div class="col-sm-auto ps-0">
                        <button class="btn btn-primary" type="button" id="searchbutton">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>

                    <button class="btn" type="button" id="resetbutton">
                        Reset
                    </button>
                </div>

                <div class="d-flex">
                    <a id="featuredLayersButton" href="#featuredLayersAccordion" data-bs-toggle="collapse" style="color:white"><button class="btn btn-primary">Featured Layers<i class="fa fa-chevron-down ps-2"></i></button></a>
                </div>


                <div class="d-flex" style="align-items: center;">

                    <label data-bs-toggle="tooltip" title="" class="d-flex mb-0 me-3 datasource-filter btn" style="background-color: orange;" data-original-title="Official Australian Placenames">
                        <div class="ps-1 pe-1">
                            ANPS Gazetteer
                        </div>
                        <input type="checkbox" id="searchausgaz" name="searchausgaz" style="margin-top: 2px; cursor:pointer" checked>
                    </label>
                    <label data-bs-toggle="tooltip" title="" class="d-flex mb-0 me-3 datasource-filter btn" style="background-color: #FE6A1B;" data-original-title="Composite Gazetteer of Australia">
                        <div class="ps-1 pe-1">
                            NCG Gazetteer
                        </div>
                        <input type="checkbox" id="searchncg" name="searchncg" style="margin-top: 2px; cursor:pointer" checked>
                    </label>
                    <label data-bs-toggle="tooltip"  class="d-flex mb-0 me-2 datasource-filter btn" style="background-color: #FFD580;" data-original-title="Contributed layers">
                        <div class="ps-1 pe-1">
                            Layers
                        </div>
                        <input type="checkbox" id="searchpublicdatasets" name="searchpublicdatasets" style="margin-top: 2px; cursor:pointer" checked >
                    </label>

                    <div class="d-flex view-button ps-5" style="align-items: baseline;">
                        <label class="radio" id="radio-map">
                            <input type="radio" name="typeFilter" class="typeFilter-map">
                            <span class="label-body ps-1">Points</span>
                        </label>
                        <label class="radio" id="radio-map" style="padding-left: 3rem;">
                            <input type="radio" name="typeFilter" class="typeFilter-cluster">
                            <span class="label-body ps-1">Cluster</span>
                        </label>
                        <label class="radio" id="radio-list" style="padding-left: 3rem; padding-right: 2rem;">
                            <input type="radio" name="typeFilter" class="typeFilter-list">
                            <span class="label-body ps-1">List</span>
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
  <div class="bg-light border-top py-4">
    <div class="container">

      <div class="row g-4">

        {{-- 1) FILTERS --}}
        <div class="col-12 col-lg-4">
          <div class="card h-100">
            <div class="card-body">

              <div class="d-flex align-items-center gap-2 mb-3">
                <h4 class="mb-0">Filters</h4>
                <span tabindex="0"
                      data-bs-html="true"
                      data-bs-animation="true"
                      class="bi bi-question-circle"
                      data-bs-toggle="tooltip"
                      data-bs-placement="right"
                      style="font-size:14px"
                      title="Some records may not be comprehensively tagged. Tags for records may differ between states.">
                </span>
              </div>

              {{-- Search Description + Limit to --}}

              <div id="home-description-numplaces" class="vstack gap-3">

                <div class="row align-items-center">
                  <div class="col">
                    <div class="form-check d-flex align-items-center gap-2">

                      <input class="form-check-input mt-0"
                            type="checkbox"
                            id="searchdescription"
                            name="searchdescription">

                      <label class="form-check-label mb-0" for="searchdescription">
                        Search Description
                      </label>

                      <span tabindex="0"
                            data-bs-html="true"
                            data-bs-animation="true"
                            class="bi bi-question-circle"
                            data-bs-toggle="tooltip"
                            data-bs-placement="right"
                            style="font-size:14px"
                            title="Also search Description field">
                      </span>

                    </div>
                  </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                  <div class="fw-normal">Limit to</div>
                  <select class="form-select w3-white num-places" style="width:auto" id="num-places">
                    <option value="100">100 places</option>
                    <option value="200">200 places</option>
                    <option value="500">500 places</option>
                    <option value="2000">2000 places</option>
                    <option value="5000">5000 places</option>
                  </select>
                </div>

              

              {{-- Add filter control --}}
              <div class="row g-2 align-items-center mb-3">
                <div class="col-12 col-md-8">
                  <select class="form-select w3-white" name="filterType" id="filterType">
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
                <div class="col-12 col-md-4 d-grid">
                  <button id="addFilter" class="btn btn-primary" type="button">Add</button>
                </div>
              </div>

              {{-- Existing dynamic filters container (keep ids + inner controls exactly) --}}
              <div id="filtersContainer" class="vstack gap-2">

                <div class="row g-2 align-items-center" id="filter-Place-Type" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">Place Type:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <select class="form-select w3-white" name="recordtype" id="recordtype">
                        <option label="" selected></option>
                        @foreach($recordtypes as $recordtype)
                          <option label="{{ $recordtype->type }}">{{ $recordtype->type }}</option>
                        @endforeach
                      </select>
                    <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-Layers" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">Layers:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="text" class="form-control w3-white" id="searchlayers" autocomplete="off">
                      <input type="hidden" name="searchlayers" id="selected-layers">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-Extended-Data" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">
                    <a href="{{ config('app.tlcmap_doc_url') }}/help/guides/guide/"
                      style="color:#000000; text-decoration:none;"
                      target="_blank"
                      data-bs-toggle="tooltip"
                      title="This enables nuanced search and map creation within layers and needs special syntax, see under 'Search' in the Guide.">
                      Extended Data?
                    </a>
                  </div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="text" class="form-control w3-white" name="extended_data" id="extended_data" autocomplete="off">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-LGA" style="display:none;" data-bs-toggle="tooltip" title="Local Government Area.">
                  <div class="col-12 col-sm-5 d-flex align-items-center">LGA:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="text" class="form-control w3-white" name="lga" id="lga" autocomplete="off">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-State-Territory" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">State/Territory:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <select class="form-select w3-white" name="state" id="state">
                        <option label="" selected></option>
                        @foreach($states as $state)
                          <option label="{{ $state }}">{{ $state }}</option>
                        @endforeach
                      </select>
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-Parish" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">Parish:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="text" class="form-control w3-white" id="parish" name="parish" autocomplete="off">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-Feature" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">Feature:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="text" class="form-control w3-white" id="feature" name="feature" autocomplete="off">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-From-ID" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">From ID:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="text" class="form-control w3-white" id="fromid" name="fromid">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-To-ID" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">To ID:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="text" class="form-control w3-white" id="toid" name="toid">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-Date-From" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">Date From:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="date" class="form-control w3-white" id="datefrom" name="datefrom">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

                <div class="row g-2 align-items-center" id="filter-Date-To" style="display:none;">
                  <div class="col-12 col-sm-5 d-flex align-items-center">Date To:</div>
                  <div class="col-12 col-sm-7">
                    <div class="d-flex align-items-center">
                      <input type="date" class="form-control w3-white" id="dateto" name="dateto">
                      <span tabindex="0" class="bi bi-x-circle remove-filter-button ms-2"></span>
                    </div>
                  </div>
                </div>

              </div>

              </div>

            </div>
          </div>
        </div>

        {{-- 2) SEARCH WITHIN REGION --}}
        <div class="col-12 col-lg-4">
          <div class="card h-100">
            <div class="card-body">

              <div class="d-flex align-items-center gap-2 mb-3">
                <h4 class="mb-0">Search within region</h4>
                <span tabindex="0"
                      data-bs-html="true"
                      data-bs-animation="true"
                      class="bi bi-question-circle"
                      data-bs-toggle="tooltip"
                      data-bs-placement="right"
                      style="font-size:14px"
                      title="Use the shapes at top right of the map to draw a region on the map, or provide details below.">
                </span>
              </div>

              <div class="row g-2 align-items-center mb-3">
                <div class="col-12 col-md-7">
                  <select id="mapselector" class="form-select">
                    <option id="bboxoption" value="bboxoption">Bounding Box</option>
                    <option id="polygonoption" value="polygonoption">Polygon</option>
                  </select>
                </div>
                <div class="col-12 col-md-5 d-grid">
                  <button class="btn btn-primary" id="mapdraw" type="button">Draw</button>
                </div>
              </div>

              <div id="bboxdiv" class="vstack gap-2">
                <input type="hidden" id="bbox" name="bbox" value="">

                <div class="fw-semibold">Longitude</div>
                <div class="input-group">
                  <input type="text" class="form-control w3-white" id="minlong" placeholder="min long">
                  <span class="text-muted mx-2 align-self-center">to</span>
                  <input type="text" class="form-control w3-white" id="maxlong" placeholder="max long">
                </div>

                <div class="fw-semibold">Latitude</div>
                <div class="input-group">
                  <input type="text" class="form-control w3-white" id="minlat" placeholder="min lat">
                  <span class="text-muted mx-2 align-self-center">to</span>
                  <input type="text" class="form-control w3-white" id="maxlat" placeholder="max lat">
                </div>
              </div>

              <div id="polygondiv" class="hidden mt-3">
                <input type="hidden" id="polygon" name="polygon" value="">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span class="fw-semibold">Points</span>
                  <input type="text"
                         class="form-control w3-white"
                         id="polygoninput"
                         placeholder="0 0, 0 100, 100 100, 100 0, 0 0">
                </div>
              </div>

            </div>
          </div>
        </div>

        {{-- 3) BULK PLACE NAMES + KML POLYGON --}}
        <div class="col-12 col-lg-4">
          <div class="card h-100">
            <div class="card-body">

              <div class="bulk-placename-search mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <h4 class="mb-0">Search for a list of place names</h4>
                  <span tabindex="0"
                        data-bs-html="true"
                        data-bs-animation="true"
                        class="bi bi-question-circle"
                        data-bs-toggle="tooltip"
                        data-bs-placement="right"
                        style="font-size:14px"
                        title="Upload a file containing a list of place names, either one per line or separated by commas.">
                  </span>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <input type="file"
                         name="bulkfileinput"
                         id="bulkfileinput"
                         class="form-control"
                         style="max-width: 100%; font-size:14px;">
                  <button type="button" class="btn btn-danger" id="bulkfileCancel" hidden>&times;</button>
                </div>
              </div>

              <div class="kml-search">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <h4 class="mb-0">Search within a KML polygon</h4>
                  <span tabindex="0"
                        data-bs-html="true"
                        data-bs-animation="true"
                        class="bi bi-question-circle"
                        data-bs-toggle="tooltip"
                        data-bs-placement="right"
                        style="font-size:14px"
                        title="Upload a KML file specifying a polygon. No visualisation. Downloadable results only.">
                  </span>
                </div>

                {{-- NB: modal content lives elsewhere (as in your comment) --}}
                <button type="button"
                        class="btn btn-outline-dark"
                        data-bs-toggle="modal"
                        data-bs-target="#kmlPolygonSearchModal">
                  Choose file
                </button>
              </div>

            </div>
          </div>
        </div>

      </div> {{-- /row --}}

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
        

            <div class="container-fluid">
                <div class="place-list pt-4"></div>
            </div>

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