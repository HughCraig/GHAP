@extends('templates.layout')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/map-picker.css') }}">
@endpush

@push('scripts')

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script src="{{ asset('js/map-picker.js') }}"></script>
<script src="{{ asset('js/message-banner.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>

<script src="{{ asset('/js/texts.js') }}"></script>
@endpush

@section('content')

<h2>Text</h2>
<a href="{{url('myprofile/mytexts')}}" class="btn btn-primary">Back</a>
<a href="{{url()->full()}}/parse" class="btn btn-primary">Parse Text</a>
<a href="{{url('myprofile/mytexts')}}" class="btn btn-primary">Download</a>

<!-- Quick Info -->
<div class="row mt-3">
    <div class="col-lg-4">
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th class="w-25">Name</th>
                    <td>{{$text->name}}</td>
                </tr>
                <tr style="height: 50px; overflow: auto">
                    <th>Type</th>
                    <td>{{$text->texttype->type}}</td>
                </tr>
                <tr style="height: 50px; overflow: auto">
                    <th>Description</th>
                    <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($text->description) !!}</td>
                </tr>

                <tr>
                    <th>Content Warning</th>
                    <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($text->warning) !!}</td>
                </tr>
                <tr>
                    <th>Contributor</th>
                    <td>{{$text->ownerName()}}</td>
                </tr>
                <tr>
                    <th>Added to System</th>
                    <td>{{$text->created_at}}</td>
                </tr>
                <tr>
                    <th>Updated in System</th>
                    <td id="dsupdatedat">{{$text->updated_at}}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-responsive" style="overflow: unset">
            <table class="table table-bordered">
                <tr>
                    <th>Creator</th>
                    <td>{{$text->creator}}</td>
                </tr>
                <tr>
                    <th>Publisher</th>
                    <td>{{$text->publisher}}</td>
                </tr>
                <tr>
                    <th>Contact</th>
                    <td>{{$text->contact}}</td>
                </tr>
                <tr>
                    <th>Citation</th>
                    <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($text->citation) !!}</td>
                </tr>
                <tr>
                    <th>DOI</th>
                    <td id="doi">{{$text->doi}}</td>
                </tr>
                <tr>
                    <th>Source URL</th>
                    <td id="source_url">{{$text->source_url}}</td>
                </tr>
                <tr>
                    <th>Linkback</th>
                    <td id="linkback">{{$text->linkback}}</td>
                </tr>
                <tr>
                    <th>Date From</th>
                    <td>{{$text->temporal_from}}</td>
                </tr>
                <tr>
                    <th>Date To</th>
                    <td>{{$text->temporal_to}}</td>
                </tr>

            </table>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th class="w-25">Subject</th>
                    <td>
                        @for($i = 0; $i < count($text->subjectKeywords); $i++)
                            @if($i == count($text->subjectKeywords)-1)
                            {{$text->subjectKeywords[$i]->keyword}}
                            @else
                            {{$text->subjectKeywords[$i]->keyword}},
                            @endif
                            @endfor
                    </td>
                </tr>
                <tr>
                    <th>Image</th>
                    <td>
                        @if($text->image_path)
                        <img src="{{ asset('storage/images/' . $text->image_path) }}" alt="Layer Image" style="max-width: 100%; max-height:150px">
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Language</th>
                    <td>{{$text->language}}</td>
                </tr>
                <tr>
                    <th>License</th>
                    <td>{{$text->license}}</td>
                </tr>
                <tr>
                    <th>Usage Rights</th>
                    <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($text->rights) !!}</td>
                </tr>
                <tr>
                    <th>Date Created (externally)</th>
                    <td>{{$text->created}}</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Displaying the text content in a scrollable div  MUFENG: make this class-->
<div class="p-4" style="border: 1px solid #ddd;width: 100%; height: 300px; overflow-y: scroll; white-space: pre-line;">
    {!! nl2br(htmlspecialchars_decode($text->content)) !!}
</div>

<h2>Layers created from this text</h2>
<a href="{{url()->full()}}/parse" class="btn btn-primary mb-4">Create Layer From Text</a>

<table id="datasettable" class="display" style="width:100%">
    <thead class="w3-black">
        <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Content Warning</th>
            <th>Created</th>
            <th>Updated</th>
        </tr>
    </thead>
    <tbody>
        @foreach($text->datasets as $ds)
        <tr id="row_id_{{$ds->id}}">
            <td><a href="/myprofile/mydatasets/{{$ds->id}}">{{$ds->name}}</a></td>
            <td>{{$ds->recordtype->type}}</td>
            <td>{!! \TLCMap\Http\Helpers\HtmlFilter::simple($ds->warning) !!}</td>
            <td>{{$ds->created_at}}</td>
            <td>{{$ds->updated_at}}</td>
        </tr>
        @endforeach
    </tbody>
</table>


@endsection