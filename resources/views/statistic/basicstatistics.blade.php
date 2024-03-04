@extends('templates.layout')

@push('scripts')
<script>
    var statistics = @json($statistic);
    var layer_name = "{{  $ds->name }}";
</script>
<script src="{{ asset('/js/basicstatistics.js') }}"></script>
@endpush


@section('content')
<div class="container mt-4">
    <h2 class="pb-4">Basic Statistics</h2>

    <button class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false" onclick="window.open('{{ config('app.views_root_url') }}/journey.html?load=' + encodeURIComponent('{{ url('/layers/' . $ds->id . '/basicstatistics/json') }}'))">
        🌏 View Map
    </button>

    <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="downloadDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Download
        </button>
        <div class="dropdown-menu" aria-labelledby="downloadDropdown">
            <a class="dropdown-item grab-hover" id="download-csv">CSV</a>
            <a class="dropdown-item grab-hover" href="{{url()->full()}}/json/download">GeoJSON</a>
        </div>
    </div>

    <p class="pt-4">To understand this analysis, check the <a href="https://tlcmap.org/help/guides/ghap-guide/">GHAP Guide</a></p>

    <table class="table table-bordered" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Statistic</th>
                <th>Value</th>
                <th>Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statistic as $stat)
            <tr>
                <td>
                    <div data-toggle="tooltip" title="{{ $stat['explanation'] }}">
                        {{ $stat['name'] }}
                    </div>
                </td>
                <td>
                    @if(isset($stat['url']) && !is_null($stat['url']))
                    <a href="{{ $stat['url'] }}" target="_blank">
                        @if(is_array($stat['value']))
                        <ul>
                            @foreach($stat['value'] as $key => $value)
                            <li>{{ $key }}: {{ $value }}</li>
                            @endforeach
                        </ul>
                        @else
                        {{ $stat['value'] }}
                        @endif
                    </a>
                    @elseif(is_array($stat['value']))
                    <ul>
                        @foreach($stat['value'] as $key => $value)
                        <li>{{ $key }}: {{ $value }}</li>
                        @endforeach
                    </ul>
                    @else
                    {{ $stat['value'] }}
                    @endif
                </td>
                <td>{!! $stat['unit'] ?? '-' !!}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<script>

</script>

@endsection