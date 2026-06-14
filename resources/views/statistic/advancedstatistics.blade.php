@extends('templates.layout')

@push('scripts')
<script>
    var statistics = @json($statistic);
    var layer_name = "{{  $ds->name }}";
</script>
<script src="{{ asset('/js/stmetrics-csv-download.js') }}"></script>
<script src="{{ asset('/js/basicstatistics.js') }}"></script>
@endpush

@section('content')
<div class="container mt-4 advanced-statistics">
    <h2 class="pb-4">Advanced Statistics</h2>

    <button id="download-csv" class="btn btn-primary" type="button" aria-haspopup="true" aria-expanded="false">
        Download CSV
    </button>

    <p class="pt-4">To understand this analysis, check the <a href="{{ config('app.tlcmap_doc_url') }}/help/guides/guide/">Guide</a></p>

    <table class="table table-bordered">
        <tbody>
            @php
            $htmlFields = ['description', 'warning', 'citation', 'rights'];
            $metadataItems = collect($ds->getMetadata())->map(function($value, $key) use ($htmlFields) {
            $label = ucwords(str_replace('_', ' ', $key));
            if (is_array($value)) {
            $display = e(implode(', ', $value));
            } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
            $display = '<a href="' . e($value) . '" target="_blank">' . e($value) . '</a>';
            } elseif (in_array($key, $htmlFields)) {
            $display = $value;
            } else {
            $display = e($value);
            }
            return ['label' => $label, 'display' => $display];
            })->values()->all(); @endphp
            @foreach(array_chunk($metadataItems, 2) as $row)
            <tr>
                <td><strong>{{ $row[0]['label'] }}</strong></td>
                <td>{!! $row[0]['display'] !!}</td>
                @if(isset($row[1]))
                <td><strong>{{ $row[1]['label'] }}</strong></td>
                <td>{!! $row[1]['display'] !!}</td>
                @else
                <td></td>
                <td></td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="table table-bordered" style="margin-top: 40px;">
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
                    <div data-bs-toggle="tooltip" title="{{ $stat['explanation'] }}">
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