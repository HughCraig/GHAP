@extends('rocrate.base')

@php
    // Get the dataset data entity IDs
    $datasetEntityIDs = [];
    if (isset($metadata)) {
        foreach ($metadata['@graph'] as $entity) {
            if ($entity['@id'] === './') {
                foreach ($entity['hasPart'] as $item) {
                    $datasetEntityIDs[] = $item['@id'];
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
                    @elseif ($name === 'license')
                        <tr>
                            <th><a href="http://schema.org/{{ $name }}">{{ $name }}</a></th>
                            <td>
                                @foreach ($metadata['@graph'] as $entity2)
                                    @if ($entity2['@id'] === $value['@id'])
                                        {{ $entity2['name'] }}
                                        @break
                                    @endif
                                @endforeach
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
    @if (!empty($datasetEntityIDs))
        <h2>Layers</h2>
        <ul>
            @foreach ($metadata['@graph'] as $entity)
                @if ($entity['@type'] === 'Dataset' && in_array($entity['@id'], $datasetEntityIDs))
                    <li><a href="#layer{{ $loop->index }}">{{ $entity['name'] }}</a></li>
                @endif
            @endforeach
        </ul>
        @foreach ($metadata['@graph'] as $entity)
            @if ($entity['@type'] === 'Dataset' && in_array($entity['@id'], $datasetEntityIDs))
                @include('rocrate.partial_dataset', ['datasetEntity' => $entity, 'level' => 3, 'index' => $loop->index])
            @endif
        @endforeach

        <h2>Saved Searches</h2>
        <ul>
            @foreach ($metadata['@graph'] as $entity)
                @if ($entity['@type'] === 'Saved search' && in_array($entity['@id'], $datasetEntityIDs))
                    <li><a href="#layer{{ $loop->index }}">{{ $entity['name'] }}</a></li>
                @endif
            @endforeach
        </ul>
        @foreach ($metadata['@graph'] as $entity)
            @if ($entity['@type'] === 'Saved search' && in_array($entity['@id'], $datasetEntityIDs))
                @include('rocrate.partial_dataset', ['datasetEntity' => $entity, 'level' => 3, 'index' => $loop->index])
            @endif
        @endforeach
    @endif
@endsection
