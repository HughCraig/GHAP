@extends('rocrate.base')

@php
    // Get the IDs of the files belong to this dataset.
    $fileIDs = [];
    if (isset($metadata)) {
        foreach ($metadata['@graph'] as $entity) {
            if ($entity['@id'] === './') {
                foreach ($entity['hasPart'] as $item) {
                    $fileIDs[] = $item['@id'];
                }
                break;
            }
        }
    }
@endphp

@section('content')
    @foreach ($metadata['@graph'] as $entity)
        @if ($entity['@id'] === './')
            <h1>{{ $entity['name'] }}</h1>
            <table class="info">
                <tbody>
                @foreach ($entity as $name => $value)
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
            @break
        @endif
    @endforeach
    <h2>Files</h2>
    @foreach ($metadata['@graph'] as $entity)
        @if ($entity['@type'] === 'File' && in_array($entity['@id'], $fileIDs))
            <h3>{{ $entity['name'] }}</h3>
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
@endsection
