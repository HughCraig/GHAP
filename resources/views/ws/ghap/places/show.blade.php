@extends('templates.layout')

@push('scripts')
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        var ajaxsavesearch = "{{url('ajaxsavesearch')}}";
    </script>
    <script src="{{ asset('/js/subsearch.js') }}"></script>
    <script src="{{ asset('/js/savesearch.js') }}"></script>
@endpush

@section('content')

    <div>
        <h2>Search Results</h2>
        @if(isset($details))
        <form method="post" action="{{ url('places/download/') }}">
            {{ csrf_field() }}
            <div>
                <script> var downloadurl = window.location.href.replace(/&?\??paging=[0-9]*/, '').replace(/&?\??page=[0-9]*/, '').replaceAll('+', '%20'); </script>

                <a href="{{route('index')}}#advancedsearch" class="btn btn-secondary tlcmgreen" role="button" id="advancedsearch" title="Search within an area, exact or fuzzy matching and filter by attributes.">Advanced Search</a>


                <!-- Export/Download -->
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle tlcmgreen" type="button" id="downloadDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Download
                    </button>
                    <div class="dropdown-menu" aria-labelledby="downloadDropdown">
                        <a class="dropdown-item grab-hover" href="{{url()->full()}}&format=kml&download=on">KML</a>
                        <a class="dropdown-item grab-hover" href="{{url()->full()}}&format=csv&download=on">CSV</a>
                        <a class="dropdown-item grab-hover" href="{{url()->full()}}&format=json&download=on">GeoJSON</a>
                    </div>
                </div>

                <!-- Web Services Feed -->
                <div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle tlcmgreen" type="button" id="wsfeedDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        WS Feed
                    </button>
                    <div class="dropdown-menu" aria-labelledby="wsfeedDropdown">
                        <a class="dropdown-item grab-hover" href="{{url()->full()}}&format=kml">KML</a>
                        <a class="dropdown-item grab-hover" href="{{url()->full()}}&format=csv">CSV</a>
                        <a class="dropdown-item grab-hover" href="{{url()->full()}}&format=json">GeoJSON</a>
                    </div>
                </div>

                @if (!empty(config('app.views_root_url')))
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle tlcmorange" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        &#x1F30F View Maps...
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load='+encodeURIComponent(downloadurl+'&format=json'));">3D Viewer</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load='+encodeURIComponent(downloadurl+'&format=json'));">Cluster</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?line=route&load='+encodeURIComponent(downloadurl+'&format=json'));">Journey Route</a>
                            <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?&load='+encodeURIComponent(downloadurl+'&format=json'));">Werekata Flight by Route</a>
                            @if (!empty(config('app.views_temporal_earth_url')))
                                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file='+encodeURIComponent(downloadurl+'&format=kml'));">Temporal Earth</a>
                            @endif
                        </div>
                    </div>
                @endif
                
            </div>

            <div class="mt-4 mb-1"><p>Note: Layers are contributed from many sources by many people or derived by computer 
                and are the responsibility of the contributor.
                Layers may be incomplete and locations and dates may be imprecise.
                Check the layer for details about the source. Absence in TLCMap does not indicate absence in reality. 
                Use of TLCMap may inform heritage research but is not a substitute for established formal and legal processes and consultation.</p>
            </div>

            <!-- save search -->
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

        </form>

        <div class="form-group row">
            <div class="col-xs-4">
                <div class="p-3 w3-pale-blue">
                    Displaying <b>{{ count($details) }}</b>
                    @if(count($details) != 1)
                        results
                    @else
                        result
                    @endif
                    from a total of <b>{{ $details->total() }}</b>:
                </div>
            </div>
        </div>

        
        <div id="datasettable_wrapper" class="dataTables_wrapper no-footer mb-2">

            <div id="datasettable_filter" class="dataTables_filter" style="padding-right: 20px;">
                @if( app('request')->input('subquery') )
                    <script>var cancelSearch = window.location.href.replace(/&?\??subquery=.*/, '');</script>
                    <button class="btn btn-secondary" onclick="location.href=cancelSearch">Cancel Sub-query</button>
                @else
                    <label>
                        Sub-query:<input id="subsearchInput" type="search" style="padding: 6px;" class="" placeholder="Search within results..." aria-controls="datasettable">
                        <button type="button" class="btn btn-primary" id="subsearchButton" onclick="subsearch()"><i class="fa fa-search"></i></button>
                    </label>
                @endif
            </div>
        </div>

        <div id="searchresults" style="max-width:100%;">
            @include('templates.table')
            @if(count($details) == 0)
                <div class="text-center font-weight-bold">No Results Found!</div>
            @endif
        </div>
        @endif

        @if(isset($message))
            <h2>{{ $message }}</h2>
        @endif

        @if(isset($alert))
            <script>
                $( document ).ready(function() { alert("{{$alert}}") });
            </script>
        @endif

        {{ $details->appends(request()->query())->links() }}
        
    </div>
@endsection
