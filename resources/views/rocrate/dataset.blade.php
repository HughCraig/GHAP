@extends('rocrate.base')

@section('content')
    @foreach ($metadata['@graph'] as $entity)
        @if ($entity['@id'] === './')
            @include('rocrate.partial_dataset', ['datasetEntity' => $entity])
            @break
        @endif
    @endforeach
@endsection
