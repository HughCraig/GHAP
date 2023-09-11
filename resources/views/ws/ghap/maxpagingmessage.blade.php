@extends('templates.layout')

@section('content')
<h2>Maximum Paging Reached</h2>
<div>
    <span class="d-block mb-4">Your query has requested more results or results per page than the system can currently handle.</span>
    <span class="d-block mb-4">The current maximum results per page is <b>{{ config('app.maxpaging') }}</b>.</span>
    <span class="d-block mb-4">Your results have been limited to show only this many entries per page (for html) or this many entries total (for kml, json, or csv).</span>
    <form action="maxpagingredirect"><button type="submit" class="btn btn-primary btn-lg">Continue</button></form>
</div>

@endsection