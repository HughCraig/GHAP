<div class="container">

    <div class="row">
        <div class="col">
            <p>
                SORT:
                <!-- @sortablelink('title', 'Title') Don't know why, but this sorter doesn't work, so just commenting out for now.-->
                @sortablelink('placename', 'Placename') |
                @sortablelink('state_code', 'State') |
                @sortablelink('lga_name', 'LGA') |
                @sortablelink('feature_term') |
                @sortablelink('tlcm_latitude', 'Latitude') |
                @sortablelink('tlcm_longitude', 'Longitude') |
                @sortablelink('tlcm_start', 'Start Date') |
                @sortablelink('tlcm_end', 'End Date') |
            </p>
        </div>
    </div>

    @foreach($details as $line)
        <div class="row">
            <div class="col">
                <div class="sresultmain">
                    <h4>
                        <button type="button" class="btn btn-primary btn-sm"
                                onclick="copyLink(@if(isset($line->anps_id))'a{{base_convert($line->anps_id,10,16)}}',this,'id'@elseif(isset($line->dataitem_id))'t{{base_convert($line->dataitem_id,10,16)}}',this,'id'@endif)">
                            C
                        </button>
                        <a href="{{URL::to('/')}}/search?@if(isset($line->anps_id))id=a{{base_convert($line->anps_id,10,16)}}@elseif(isset($line->dataitem_id))id=t{{base_convert($line->dataitem_id,10,16)}}@endif">
                            @if(isset($line->title)){{$line->title}}@else{{$line->placename}}@endif
                        </a>
                    </h4>
                    <dl>
                        @if(isset($line->placename))
                            <dt>Placename</dt>
                            <dd>{{$line->placename}}</dd>
                        @endif
                        @if(isset($line->dataitem_id))
                            <dt>Layer</dt>
                            <dd><a href="{{route('publicdatasets')}}/{{$line->dataset_id}}">{{$line->dataset->name}}</a></dd>
                        @else
                            <dt>Layer</dt>
                            <dd><a href="https://www.anps.org.au/">Australian National Placenames Survey Gazetteer</a></dd>
                        @endif
                        @if(isset($line->external_url))
                            <dt>Link back to source:</dt>
                            <dd>
                                <a href="{{$line->external_url}}">@if(isset($line->external_url)){{$line->original_data_source}}@else{{$line->external_url}}@endif</a>
                            </dd>
                        @endif
                        @if(isset($line->recordtype_id))
                            <dt>Type</dt>
                            <dd>{{$line->recordtype->type}}</dd>
                        @elseif(isset($line->dataset->recordtype_id))
                            <dt>Type</dt>
                            <dd>{{$line->dataset->recordtype->type}}</dd>
                        @endif
                    </dl>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            🌏 View Place In...
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @if (!empty(config('app.views_root_url')))
                                @if(isset($line->anps_id))
                                    <a class="dropdown-item grab-hover"
                                        onclick="window.open(`{{ config('app.views_root_url') }}/places.html?load={{env('APP_URL')}}/search?id%3Da{{base_convert($line->anps_id,10,16)}}%26format%3Djson`)">
                                        3D Viewer
                                    </a>
                                @elseif(isset($line->dataitem_id))
                                    <a class="dropdown-item grab-hover"
                                        onclick="window.open(`{{ config('app.views_root_url') }}/places.html?load={{env('APP_URL')}}/search?id%3Dt{{base_convert($line->dataitem_id,10,16)}}%26format%3Djson`)">
                                        3D Viewer
                                    </a>
                                @endif
                            @endif

                            @if (!empty(config('app.views_temporal_earth_url')))
                                @if(isset($line->anps_id))
                                    <a class="dropdown-item grab-hover"
                                        onclick="temporalEarthLink('a{{base_convert($line->anps_id,10,16)}}','id')">
                                        Temporal Earth
                                    </a>
                                @elseif(isset($line->dataitem_id))
                                    <a class="dropdown-item grab-hover"
                                        onclick="temporalEarthLink('t{{base_convert($line->dataitem_id,10,16)}}', 'id')">
                                        Temporal Earth</a>
                                @endif
                            @endif

                            @if(isset($line->tlcm_latitude))
                                <a class="dropdown-item" target="_blank"
                                    href="https://www.google.com/maps/search/?api=1&query={{$line->tlcm_latitude}},{{$line->tlcm_longitude}}"
                                    target="_blank">
                                    Google Maps
                                </a>
                            @endif
                            @if(isset($line->placename))
                                <a class="dropdown-item grab-hover" target="_blank"
                                    href="https://trove.nla.gov.au/search?keyword={{$line->placename}}">
                                    Trove Search
                                </a>
                            @else
                                <a class="dropdown-item grab-hover"
                                    href="https://trove.nla.gov.au/search?keyword={{$line->title}}">
                                    Trove Search
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div>
                    <h4>Details</h4>
                    <dl>
                        @if(isset($line->tlcm_latitude))
                            <dt>Latitude</dt>
                            <dd>{{$line->tlcm_latitude}}</dd>
                        @endif
                        @if(isset($line->tlcm_longitude))
                            <dt>Longitude</dt>
                            <dd>{{$line->tlcm_longitude}}</dd>
                        @endif
                        @if(isset($line->tlcm_start))
                            <dt>Start Date</dt>
                            <dd>{{$line->tlcm_start}}</dd>
                        @endif
                        @if(isset($line->tlcm_end))
                            <dt>End Date</dt>
                            <dd>{{$line->tlcm_end}}</dd>
                        @endif
                        @if(isset($line->state_code))
                            <dt>State</dt>
                            <dd>{{$line->state_code}}</dd>
                        @endif
                        @if(isset($line->lga_name))
                            <dt>LGA</dt>
                            <dd>{{$line->lga_name}}</dd>
                        @endif
                        @if(isset($line->parish))
                            <dt>Parish</dt>
                            <dd>{{$line->parish}}</dd>
                        @endif
                        @if(isset($line->feature_term))
                            <dt>Feature Term</dt>
                            <dd>{{$line->feature_term}}</dd>
                        @endif
                    </dl>
                </div>
            </div>
            <div class="col">
                <h4>Description</h4>
                <div>
                    <dl>
                        @if(isset($line->dataset->warning))
                            <dt style="background-color: #ffcc00;">Layer Warning:</dt>
                            <dd style="background-color: #ffcc00;">{{$line->dataset->warning}}</dd>
                        @endif
                        @if(isset($line->description))
                            <dd>{!!$line->description!!}</dd>
                        @endif
                    </dl>
                </div>
            </div>
            <div class="col">
                <div>
                    <h4>Sources</h4>
                    @if(isset($line->anps_id))
                        <dt>ANPS ID</dt>
                        <dd>{{base_convert($line->anps_id,10,16)}}</dd>
                    @endif
                    @if(isset($line->dataitem_id))
                        <dt>TLCMap ID</dt>
                        <dd>{{base_convert($line->dataitem_id,10,16)}}</dd>
                    @endif
                    @if(isset($line->original_data_source))
                        <dt>Source</dt>
                        <dd>{{$line->original_data_source}}</dd>
                    @endif
                    @if(isset($line->dataset->flag))
                        <dt>ANPS to TLCMap Import Note</dt>
                        <dd>{{$line->dataset->flag}}</dd>
                    @endif
                    @isset($sources)
                        @if(!(empty($sources[$line->anps_id])))
                            <p>ANPS Sources</p>
                            <dl>
                                @foreach($sources[$line->anps_id] as $source)
                                    <dt>ID</dt>
                                    <dd>{{$source->source_id}}</dd>
                                    <dt>Type</dt>
                                    <dd>{{$source->source_type}}</dd>
                                    <dt>Title</dt>
                                    <dd>{{$source->title}}</dd>
                                    <dt>Author</dt>
                                    <dd>{{$source->author}}</dd>
                                    <dt>ISBN</dt>
                                    <dd>{{$source->isbn}}</dd>
                                    <dt>Publisher`</dt>
                                    <dd>{{$source->publisher}}</dd>
                                    <dt>Place</dt>
                                    <dd>{{$source->source_place}}</dd>
                                    <dt>Date</dt>
                                    <dd>{{$source->source_date}}</dd>
                                    <dt>Locaton</dt>
                                    <dd>{{$source->source_location}}</dd>
                                    <dt>Library</dt>
                                    <dd>{{$source->anps_library}}</dd>
                                    <dt>Status</dt>
                                    <dd>{{$source->source_status}}</dd>
                                    <dt>Notes</dt>
                                    <dd>{{$source->source_notes}}</dd>
                                @endforeach
                            </dl>
                        @endif
                    @endisset
                </div>
            </div>
            @if(isset($line->extended_data))
                <div class="col">
                    <h4>Extended Data</h4>{!!$line->extDataAsHTML()!!}
                </div>
            @endif

            <!-- end bootstrap row -->
        </div>

    @endforeach
<!-- end bootstrap container -->
</div>
