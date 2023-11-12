@extends('templates.layout')

@push('scripts')
    <script>
        //Put the relative URL of our ajax functions into global vars for use in external .js files
        const removeCollectionDatasetService = "{{url('ajaxremovecollectiondataset')}}";
        const removeCollectionSavedSearchService = "{{url('ajaxremovecollectionsavedsearch')}}";
    </script>
    <script src="{{ asset('js/userviewcollection.js') }}"></script>
    <script src="{{ asset('/js/collection.js') }}"></script>
@endpush

@section('content')

    <h2>View Multilayer</h2>

    <a href="{{url('myprofile/mycollections')}}" class="btn btn-primary">Back</a>

    <!-- Edit Collection Modal Button-->
    @include('modals.editcollectionmodal')

    <!-- Export/Download -->
    <div class="dropdown">
        <button class="btn btn-secondary dropdown-toggle" type="button" id="downloadDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Download
        </button>
        <div class="dropdown-menu" aria-labelledby="downloadDropdown">
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/ro-crate">RO-Crate</a>
        </div>
    </div>

    @if (!empty(config('app.views_root_url')) && $collection->public)
        <!-- Visualise-->
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                View Map
            </button>
            <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-3d.html?load=' + encodeURIComponent('{{url('multilayers')}}/{{$collection->id}}/json'))">3D Viewer</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-cluster.html?load=' + encodeURIComponent('{{url('multilayers')}}/{{$collection->id}}/json'))">Cluster</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-journey.html?load=' + encodeURIComponent('{{url('multilayers')}}/{{$collection->id}}/json?line=route'))">Journey Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-journey.html?load=' + encodeURIComponent('{{url('multilayers')}}/{{$collection->id}}/json?line=time'))">Journey Times</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-timeline.html?load=' + encodeURIComponent('{{url('multilayers')}}/{{$collection->id}}/json?sort=start'))">Timeline</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-werekata.html?load=' + encodeURIComponent('{{url('multilayers')}}/{{$collection->id}}/json'))">Werekata Flight by Route</a>
                <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/collection-werekata.html?load=' + encodeURIComponent('{{url('multilayers')}}/{{$collection->id}}/json?sort=start'))">Werekata Flight by Time</a>
            </div>
        </div>
    @endif

    
    <!-- Quick Info -->
    <div class="row mt-3">
        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th class="w-25">Name</th><td>{{$collection->name}}</td></tr>
		            <tr style="height: 50px; overflow: auto"><th>Description</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($collection->description) !!}</td></tr>
                    <tr><th>Content Warning</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($collection->warning) !!}</td></tr>
                    <tr><th>Contributor</th><td>{{$collection->ownerUser->name}} (You)</td></tr>
                    <tr><th>Entries</th><td id="datasetsCount">{{count($collection->datasets)}}</td></tr>
                    <tr><th>Visibility</th><td id="collectionPublic">@if($collection->public)Public @else Private @endif</td></tr>
                    <tr><th>Added to System</th><td>{{$collection->created_at}}</td></tr>
                    <tr><th>Updated in System</th><td id="collectionUpdatedAt">{{$collection->updated_at}}</td></tr>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive" style="overflow: unset">
                <table class="table table-bordered">
                <tr><th class="w-25">Subject</th>
                    <td>
                        @for ($i = 0; $i < count($collection->subjectKeywords); $i++)
                            @if ($i == count($collection->subjectKeywords)-1)
                                {{$collection->subjectKeywords[$i]->keyword}}
                            @else
                                {{$collection->subjectKeywords[$i]->keyword}},
                            @endif
                        @endfor
                    </td>
                </tr>
                    <tr><th>Creator</th><td>{{$collection->creator}}</td></tr>
                    <tr><th>Publisher</th><td>{{$collection->publisher}}</td></tr>
                    <tr><th>Contact</th><td>{{$collection->contact}}</td></tr>
                    <tr><th>Citation</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($collection->citation) !!}</td></tr>
                    <tr><th>DOI</th><td id="doi">{{$collection->doi}}</td></tr>
                    <tr><th>Source URL</th><td id="source_url">{{$collection->source_url}}</td></tr>
                    <tr><th>Linkback</th><td id="linkback">{{$collection->linkback}}</td></tr>
                    <tr><th>Date From</th><td>{{$collection->temporal_from}}</td></tr>
                    <tr><th>Date To</th><td>{{$collection->temporal_to}}</td></tr>
                    <tr>
                        <th>Image</th>
                        <td>
                            @if($collection->image_path)
                            <img src="{{ asset('storage/images/' . $collection->image_path) }}" alt="Collection Image" style="max-width: 100%; max-height:150px">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr><th class="w-25">Latitude From</th><td>{{$collection->latitude_from}}</td></tr>
                    <tr><th>Longitude From</th><td>{{$collection->longitude_from}}</td></tr>
                    <tr><th>Latitude To</th><td>{{$collection->latitude_to}}</td></tr>
                    <tr><th>Longitude To</th><td>{{$collection->longitude_to}}</td></tr>
                    <tr><th>Language</th><td>{{$collection->language}}</td></tr>
                    <tr><th>License</th><td>{{$collection->license}}</td></tr>
                    <tr><th>Usage Rights</th><td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($collection->rights) !!}</td></tr>
                    <tr><th>Date Created (externally)</th><td>{{$collection->created}}</td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Add dataset Modal Button-->
    @include('modals.addcollectiondatasetmodal')
    @include('modals.addsavedsearchmodal')

    @if ( !empty($collection->datasets) || !empty($collection->savedSearches) )
        <table id="datasetsTable" class="display" style="width:100%">
            <thead class="w3-black">
            <tr>
                <th>Name</th>
                <th>Size</th>
                <th>Type</th>
                <th>Content Warning</th>
                <th>Contributor</th>
                <th>Visibility</th>
                <th>Created</th>
                <th>Updated</th>
                <th>View Map</th>
                <th>Remove</th>
            </tr>
            </thead>
            <tbody>
            @foreach($collection->datasets as $ds)
                <tr id="row_id_{{$ds->id}}">
                    <td><a href="{{url('publicdatasets')}}/{{$ds->id}}">{{$ds->name}}</a></td>
                    <td>{{count($ds->dataitems)}}</td>
                    <td>{{$ds->recordtype->type}}</td>
                    <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->warning) !!}</td>
                    <td>{{$ds->ownerName()}} @if($ds->owner() == Auth::user()->id) (You) @endif</td>
                    <td>{{ $ds->public ? 'Public' : 'Private' }}</td>
                    <td>{{$ds->created_at}}</td>
                    <td>{{$ds->updated_at}}</td>
                    <td>
                        @if (!empty(config('app.views_root_url')) && $ds->public)
                            <!-- Visualise-->
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    🌏 View Maps...
                                </button>
                                <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load=' + encodeURIComponent('{{url('layers')}}/{{$ds->id}}/json'))">3D Viewer</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load=' + encodeURIComponent('{{url('layers')}}/{{$ds->id}}/json'))">Cluster</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{url('layers')}}/{{$ds->id}}/json?line=route'))">Journey Route</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{url('layers')}}/{{$ds->id}}/json?line=time'))">Journey Times</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/timeline.html?load=' + encodeURIComponent('{{url('layers')}}/{{$ds->id}}/json?sort=start'))">Timeline</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{url('layers')}}/{{$ds->id}}/json'))">Werekata Flight by Route</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{url('layers')}}/{{$ds->id}}/json?sort=start'))">Werekata Flight by Time</a>
                                    @if (!empty(config('app.views_temporal_earth_url')))
                                        <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file={{url('layers')}}/{{$ds->id}}/kml')">Temporal Earth</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </td>
                    <td>
                        <button name="remove_dataset_button" data-collection-id="{{ $collection->id }}" id="remove_dataset_button_{{$ds->id}}" type="Button">Remove</button>
                    </td>
                </tr>
            @endforeach

            @foreach($collection->savedSearches as $ss)
                <tr id="row_ss_id_{{$ss->id}}">
                    <td><a href="{{url('/search')}}{{$ss->query}}">{{$ss->name}}</a></td>
                    <td>{{$ss->count}}</td>
                    <td>Saved search</td>
                    <td></td>
                    <td></td>   
                    <td></td>
                    <td>{{$ss->created_at}}</td>
                    <td>{{$ss->updated_at}}</td>
                    <td>
                        @if (!empty(config('app.views_root_url')))
                            <!-- Visualise-->
                            <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle" type="button" id="visualiseDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    🌏 View Maps...
                                </button>
                                <div class="dropdown-menu" aria-labelledby="visualiseDropdown">
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/3d.html?load=' + encodeURIComponent('{{url('places')}}{{$ss->query}}&format=json'))">3D Viewer</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/cluster.html?load=' + encodeURIComponent('{{url('places')}}{{$ss->query}}&format=json'))">Cluster</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{url('places')}}{{$ss->query}}&format=json&line=route'))">Journey Route</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{url('places')}}{{$ss->query}}&format=json&line=time'))">Journey Times</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/timeline.html?load=' + encodeURIComponent('{{url('places')}}{{$ss->query}}&format=json&sort=start'))">Timeline</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{url('places')}}{{$ss->query}}&format=json'))">Werekata Flight by Route</a>
                                    <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_root_url') }}/werekata.html?load=' + encodeURIComponent('{{url('places')}}{{$ss->query}}&format=json&sort=start'))">Werekata Flight by Time</a>
                                    @if (!empty(config('app.views_temporal_earth_url')))
                                        <a class="dropdown-item grab-hover" onclick="window.open('{{ config('app.views_temporal_earth_url') }}?file=' + encodeURIComponent('{{url('places')}}{{$ss->query}}&format=kml')">Temporal Earth</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </td>
                    <td>
                        <button name="remove_savedsearch_button" data-collection-id="{{ $collection->id }}" id="remove_savedsearch_button_{{$ss->id}}" type="Button">Remove</button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    <a href="{{url('myprofile/mycollections')}}" class="mb-3 btn btn-primary">Back</a>
@endsection
