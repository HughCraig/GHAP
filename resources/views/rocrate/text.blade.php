@extends('rocrate.base')

@section('content')

<h2 style="margin-top: 2rem;">Text content</h2>

<div style="
        white-space: pre-line;
    flex-grow: 1;
    overflow-y: auto;
        ">
    {!! $markedText !!}
</div>

@endsection