{{--

This is a partial template to display the dataset metadata and files which can't be used alone.
It inherits the $metadata variable from the parent template, and has the following varaibles which can be specified
through the @include directive from the parent template.

- $datasetEntity: The RO-Crate data entity of the dataset.
- $level: The page level. This will adjust the heading levels accordingly. Default to 1.
- $index: The index of the current dataset. This will be used to create a bookmark at the heading. If not set, no
  bookmark will be created.

--}}

@php
    // Set the level default to 1.
    if (!isset($level)) {
        $level = 1;
    }
    // Get the IDs of the files belong to this dataset.
    $fileIDs = [];
    if (isset($datasetEntity)) {
        foreach ($datasetEntity['hasPart'] as $item) {
            $fileIDs[] = $item['@id'];
        }
    }
@endphp

<h{{ $level }}{!! isset($index) ? ' id="layer' . $index . '"' : '' !!}>{{ $datasetEntity['name'] }}</h{{ $level }}>
<table class="info">
    <tbody>
    @foreach ($datasetEntity as $name => $value)
        @if ($name === '@type')
            <tr><th>type</th><td>{{ $value }}</td></tr>
        @elseif ($name === 'url')
            <tr>
                <th><a href="http://schema.org/{{ $name }}">{{ $name }}</a></th>
                <td>
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <div>{!! \TLCMap\Http\Helpers\URL::replaceUrlToHtml($item) !!}</div>
                        @endforeach
                    @else
                        {!! \TLCMap\Http\Helpers\URL::replaceUrlToHtml($value) !!}
                    @endif
                </td>
            </tr>
        @elseif ($name === 'spatialCoverage')
            <tr>
                <th><a href="http://schema.org/{{ $name }}">{{ $name }}</a></th>
                <td>
                    @foreach ($metadata['@graph'] as $entity2)
                        @if ($entity2['@id'] === $value['@id'])
                            @foreach ($metadata['@graph'] as $entity3)
                                @if ($entity3['@id'] === $entity2['geo']['@id'])
                                    {{ $entity3['box'] }}
                                @endif
                                @break
                            @endforeach
                            @break
                        @endif
                    @endforeach
                </td>
            </tr>
        @elseif (!in_array($name, ['hasPart', '@id']))
            <tr>
                <th><a href="http://schema.org/{{ $name }}">{{ $name }}</a></th>
                <td>{!! \TLCMap\Http\Helpers\URL::replaceUrlToHtml($value) !!}</td>
            </tr>
        @endif
    @endforeach
    </tbody>
</table>
<h{{ $level + 1 }}>Files</h{{ $level + 1 }}>
@foreach ($metadata['@graph'] as $entity)
    @if ($entity['@type'] === 'File' && in_array($entity['@id'], $fileIDs))
        <h{{ $level + 2 }}>{{ $entity['name'] }}</h{{ $level + 2 }}>
        <table class="info">
            <tbody>
            @foreach ($entity as $name => $value)
                @if ($name !== '@id' && $name !== '@type')
                    <tr>
                        <th><a href="http://schema.org/{{ $name }}">{{ $name }}</a></th>
                        <td>{{ $value }}</td>
                    </tr>
                @endif
            @endforeach
            <tr><th>File</th><td><a href="{{ $entity['@id'] }}">{{ $entity['name'] }}</a></td></tr>
            </tbody>
        </table>
    @endif
@endforeach
