@extends('templates.layout')

@section('content')
    <div style="overflow:scroll;">
        <a href="{{route('index')}}"><h1>Back to Search</h1></a>
        @if(isset($details))
        <form method="post" action="/ghap/places/download/">
            {{ csrf_field() }}
            <div>
                <script> var downloadurl = window.location.href.replace(/&?\??paging=[0-9]*/, '').replace(/&?\??page=[0-9]*/, ''); </script>
                <input class="w3-black w3-button" type="button" 
                    onclick="location.href=downloadurl+'&format=kml&download=on';" value="Download as KML" />
                <input class="w3-black w3-button" type="button" 
                    onclick="location.href=downloadurl+'&format=csv&download=on';" value="Download as CSV" />
                <input class="w3-black w3-button" type="button" 
                    onclick="location.href=downloadurl+'&format=json&download=on';" value="Download as GeoJSON" />
            </div>

            <!-- save search -->
            @guest
            <div class="mt-3 mb-3 form-group row">
                <div class="col-xs-4">
                    <div class="mt-3 mb-3 p-3 w3-pale-red"><a href="{{url('/login')}}">Log in</a> to save your search queries!</div>
                </div>
            </div>
            @else
            <div class="mt-3 mb-3 form-group row">
                <div class="col-xs-4">
                    <input type="hidden" id="save_search_query" value="{{ substr(url()->full(),strpos(url()->full(),'?')) }}" />
                    <input type="hidden" id="save_search_count" value="{{ $details->total() }}" />
                    <input type="text" class="smallerinputs w3-white form-control" id="save_search_name" placeholder="Name your search" maxlength="20"/>
                    <div class="mt-3 mb-3 p-3 w3-pale-green" id="save_search_message" style="display:none">Successfully added this search to your <a href="{{url('/myprofile/mysearches')}}">saved searches</a>!</div>
                    <button class="w3-black w3-button" id="save_search_button" type="button">Save your search</button>
                </div>
            </div>
            @endguest
            <script src="{{ asset('/js/savesearch.js') }}"></script>

        </form>
            <div class="mt-3 mb-3 form-group row">
                <div class="col-xs-4">
                    <div class="mb-3 p-3 w3-pale-blue">
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
        <h2>Results</h2>
        <table class="table table-sm table-striped table-responsive-sm">
            <thead class="w3-black">
                <tr>
                    <th>Copy Link</th>
                    <th>Placename</th>
                    <th>ANPS / Dataitem id</th>
                    <th>State</th>
                    <th>LGA</th>
                    <th>Parish</th>
                    <th>Feature Term</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Original Data Source</th>
                    <th>Flag</th>
                    <th>Google Maps</th>
                    <th>Description</th>
                    <th>ANPS Source Data</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details as $line)
                <tr>
                    <td>
                        @if(isset($line->anps_id))<button type="button" onclick="copyLink({{$line->anps_id}},this)">Copy Link</button>
                        @elseif(isset($line->dataitem_id))<button type="button" onclick="copyLink({{$line->dataitem_id}},this,'dataitemid')">Copy Link</button>@endif
                    </td>
                    <td>{{$line->placename}}</td>
                    <td>@if(isset($line->anps_id)) {{$line->anps_id}} @else Dataitem {{$line->dataitem_id}}</a> @endif </td>
                    <td>@if(isset($line->state_code)) {{$line->state_code}} @else {{$line->state}}</a> @endif </td>
                    <td>{{$line->lga_name}}</td>
                    <td>{{$line->parish}}</td>
                    <td>{{$line->feature_term}}</td>
                    <td>{{$line->TLCM_Latitude}}</td>
                    <td>{{$line->TLCM_Longitude}}</td>
                    <td>@if(isset($line->ORIGINAL_DATA_SOURCE)) {{$line->ORIGINAL_DATA_SOURCE}} @elseif(isset($line->source)) {{$line->source}} @endif</td>
                    <td>@if(isset($line->flag)) {{$line->flag}} @elseif(isset($line->dataset_id)) <a href="{{route('publicdatasets')}}/{{$line->dataset_id}}">From Public Dataset {{$line->dataset_id}} </a> @endif </td> 
                    <td>
                        @if(isset($line->google_maps_link)) <a href="{{$line->google_maps_link}}">{{$line->placename}}</a>
                        @elseif(isset($line->TLCM_Latitude) && isset($line->TLCM_Longitude)) <a href="https://www.google.com/maps/{!! urldecode('%40') !!}{{$line->TLCM_Latitude}},{{$line->TLCM_Longitude}},15z">{{$line->placename}}</a>
                        @endif
                    </td>
                    <td>{{$line->description}}</td>
                    <td>
                        @isset($sources)
                            @if(!(empty($sources[$line->anps_id])))
                                <table class="table-sm table-bordered">
                                    <thead style="background-color: #cccccc">
                                        <th>Source ID</th>
                                        <th>Source type</th>
                                        <th>Source title</th>
                                        <th>Source author</th>
                                        <th>Source isbn</th>
                                        <th>Source publisher</th>
                                        <th>Source place</th>
                                        <th>Source date</th>
                                        <th>Source location</th>
                                        <th>ANPS library?</th>
                                        <th>Source status</th>
                                        <th>Source notes</th>
                                    </thead>
                                    <tbody>
                                    @foreach($sources[$line->anps_id] as $source)
                                        <tr>
                                            <td>{{$source->source_id}}</td>
                                            <td>{{$source->source_type}}</td>
                                            <td>{{$source->title}}</td>
                                            <td>{{$source->author}}</td>
                                            <td>{{$source->isbn}}</td>
                                            <td>{{$source->publisher}}</td>
                                            <td>{{$source->source_place}}</td>
                                            <td>{{$source->source_date}}</td>
                                            <td>{{$source->source_location}}</td>
                                            <td>{{$source->anps_library}}</td>
                                            <td>{{$source->source_status}}</td>
                                            <td>{{$source->source_notes}}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @endif
                        @endisset
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
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