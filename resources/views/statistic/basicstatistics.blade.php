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

    <button class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false" onclick="window.open('{{ config('app.views_root_url') }}journey.html?load=' + encodeURIComponent('{{url()->full()}}') + '/json')">
        🌏 View Map
    </button>
    <button id="download-csv" class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false">
        Download CSV
    </button>

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