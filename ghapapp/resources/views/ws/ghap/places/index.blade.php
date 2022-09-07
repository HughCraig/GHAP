@extends('templates.layout')

@section('content')
    <div id="indexdiv">
        <h1 class="mt-0 mb-3">Gazetteer of Historical Australian Places (GHAP)</h1>
        <p>Search a gazetteer of places in Australia, based on the
            <a href="https://www.anps.org.au/">Australian National Placename Survey</a> (ANPS) and layers of cultural
            information
            contributed by researchers, institutions and the community.</p>

        @include('templates.form')
    </div>
@endsection
