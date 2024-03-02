<div class="container-fluid">

    <div class="row">
        <div class="col">
            <p>
                SORT:
                <!-- @sortablelink('title', 'Title') Don't know why, but this sorter doesn't work, so just commenting out for now.-->
                @sortablelink('placename', 'Placename') |
                @sortablelink('state', 'State') |
                @sortablelink('lga', 'LGA') |
                @sortablelink('feature_term') |
                @sortablelink('latitude', 'Latitude') |
                @sortablelink('longitude', 'Longitude') |
                @sortablelink('datestart', 'Start Date') |
                @sortablelink('dateend', 'End Date') |
            </p>
        </div>
    </div>
    <div class="place-list">
        @foreach ($details as $line)
            <div class="row">
                <div class="col col-xl-3">
                    <div class="sresultmain">
                        <h4>
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="copyLink('{{ $line->uid }}',this,'id')">
                                C
                            </button>
                            <a href="{{ URL::to('/') }}/places/{{ $line->uid }}">
                                @if (isset($line->title))
                                    {{ $line->title }}@else{{ $line->placename }}
                                @endif
                            </a>
                        </h4>
                        <dl>
                            @if (isset($line->placename))
                                <dt>Placename</dt>
                                <dd>{{ $line->placename }}</dd>
                            @endif
                            @if (isset($line->dataset))
                                <dt>Layer</dt>
                                <dd><a
                                        href="{{ route('layers') }}/{{ $line->dataset_id }}">{{ $line->dataset->name }}</a>
                                </dd>
                            @elseif (isset($line->datasource))
                                <dt>Layer</dt>
                                <dd><a href="{{ $line->datasource->link }}">{{ $line->datasource->description }}</a>
                                </dd>
                            @endif
                            @if (isset($line->external_url))
                                <dt>Link back to source:</dt>
                                <dd>
                                    <a target="_blank" href="{{ $line->external_url }}">{{ $line->external_url }}</a>
                                </dd>
                            @endif
                            @if (isset($line->recordtype_id))
                                <dt>Type</dt>
                                <dd>{{ $line->recordtype->type }}</dd>
                            @elseif(isset($line->dataset->recordtype_id))
                                <dt>Type</dt>
                                <dd>{{ $line->dataset->recordtype->type }}</dd>
                            @endif
                        </dl>
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                🌏 View Place In...
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                @if (!empty(config('app.views_root_url')))
                                    <a class="dropdown-item grab-hover"
                                        onclick="window.open(`{{ config('app.views_root_url') }}/3d.html?load={{ urlencode(config('app.url') . '/places/' . $line->uid . '/json') }}`)">
                                        3D Viewer
                                    </a>
                                @endif

                                @if (!empty(config('app.views_temporal_earth_url')))
                                    <a class="dropdown-item grab-hover"
                                        onclick="temporalEarthLink('{{ $line->uid }}','id')">
                                        Temporal Earth
                                    </a>
                                @endif

                                @if (isset($line->latitude))
                                    <a class="dropdown-item" target="_blank"
                                        href="https://www.google.com/maps/search/?api=1&query={{ $line->latitude }},{{ $line->longitude }}"
                                        target="_blank">
                                        Google Maps
                                    </a>
                                @endif
                                @if (isset($line->placename))
                                    <a class="dropdown-item grab-hover" target="_blank"
                                        href="https://trove.nla.gov.au/search?keyword={{ $line->placename }}">
                                        Trove Search
                                    </a>
                                @else
                                    <a class="dropdown-item grab-hover"
                                        href="https://trove.nla.gov.au/search?keyword={{ $line->title }}">
                                        Trove Search
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col col-xl-2">
                    <div>
                        <h4>Details</h4>
                        <dl>
                            @if (isset($line->latitude))
                                <dt>Latitude</dt>
                                <dd>{{ $line->latitude }}</dd>
                            @endif
                            @if (isset($line->longitude))
                                <dt>Longitude</dt>
                                <dd>{{ $line->longitude }}</dd>
                            @endif
                            @if (isset($line->quantity))
                                <dt>Quantity</dt>
                                <dd>{{ $line->quantity }}</dd>
                            @endif
                            @if (isset($line->datestart))
                                <dt>Start Date</dt>
                                <dd>{{ $line->datestart }}</dd>
                            @endif
                            @if (isset($line->dateend))
                                <dt>End Date</dt>
                                <dd>{{ $line->dateend }}</dd>
                            @endif
                            @if (isset($line->state))
                                <dt>State</dt>
                                <dd>{{ $line->state }}</dd>
                            @endif
                            @if (isset($line->lga))
                                <dt>LGA</dt>
                                <dd>{{ $line->lga }}</dd>
                            @endif
                            @if (isset($line->parish))
                                <dt>Parish</dt>
                                <dd>{{ $line->parish }}</dd>
                            @endif
                            @if (isset($line->feature_term))
                                <dt>Feature Term</dt>
                                <dd>{{ $line->feature_term }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
                <div class="col col-xl-3">
                    <h4>Description</h4>
                    <div>
                        <dl>
                            @if (isset($line->dataset->warning))
                                <dt style="background-color: #ffcc00;">Layer Warning:</dt>
                                <dd style="background-color: #ffcc00;">{!! \TLCMap\Http\Helpers\HtmlFilter::simple($line->dataset->warning) !!}</dd>
                            @endif
                            @if (isset($line->description))
                                <dd>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($line->description) !!}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
                @if (isset($line->route_id))
                    <div class="col col-xl-2">
                        <h4>Route Details</h4>
                        <dt>Route ID</dt>
                        <dd>{{ $line->route_id }}</dd>
                        @if (isset($line->route_original_id))
                            <dt>Route Original ID</dt>
                            <dd>{{ $line->route_original_id }}</dd>
                        @endif
                        @if (isset($line->route_title))
                            <dt>Route Title</dt>
                            <dd>{{ $line->route_title }}</dd>
                        @endif
                        @if (isset($line->stop_idx))
                            <dt>Route Stop Number</dt>
                            <dd>{{ $line->stop_idx }}</dd>
                        @endif
                    </div>
                @endif
                <div class="col col-xl-2">
                    <div>
                        <h4>Sources</h4>
                        @if (isset($line->uid))
                            <dt>ID</dt>
                            <dd>{{ $line->uid }}</dd>
                        @endif
                        @if (isset($line->source))
                            <dt>Source</dt>
                            <dd>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($line->source) !!}</dd>
                        @endif
                        @if (isset($line->dataset->flag))
                            <dt>ANPS to TLCMap Import Note</dt>
                            <dd>{{ $line->dataset->flag }}</dd>
                        @endif
                    </div>
                </div>
                <div class="col col-xl-2">
                    @if (isset($line->extended_data))
                        <h4>Extended Data</h4>{!! $line->extDataAsHTML() !!}
                    @endif
                </div>
                <!-- end bootstrap row -->
            </div>
        @endforeach
    </div>
    <!-- end bootstrap container -->
</div>
