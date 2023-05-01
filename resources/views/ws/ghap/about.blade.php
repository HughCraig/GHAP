@extends('templates.layout')

@section('content')
    <h2>About</h2>

    <p><small>Current version: {{ config('app.version') }}</small></p>

    <p>Search and contribute to placenames in Australia with the&nbsp;<em>Gazetteer of Historical Australian Placenames</em>&nbsp;(GHAP, 'The Gazetteer' or 'The Gazza'). The Gazza, for the first time, makes easily available aggregated data on 'all' placenames in Australia, based on&nbsp;<a href="https://www.anps.org.au/">ANPS data</a>, including historical names. We have cleaned up coordinates for more than two thirds of 334,208 ANPS placenames and provided a user friendly search and filter interface and web service.</p>
    <p>Search by exact or fuzzy match, for all places within a region, and apply filters to narrow results. Save results in standard formats. Because people come here to search for places, it's also a great place to contribute your place related research so that other can find it. Whether placenames are new to the Gazza or already there, add them in (multiple instances are 'attestations' for people to find out about through links to your research).</p>
    <p>The Gazza can help answer that simple question - "What's here?"</p>
    <p>It has two main aspects:</p>
    <ol>
        <li>ANPS Data: Placename data aggregated by ANPS from official state and federal records and other sources. This is the 'official' record of placenames.</li>
        <li>User Contributions: Information about places contributed by researchers and community. This has several functions:
            <ul>
                <li>to enhance understanding and appreciation of meaning of place in Australia, or places important to Australians (including overseas)</li>
                <li>crowd source historical, indigenous and other placenames not already in the ANPS Gazetteer</li>
                <li>crowd source &lsquo;attestations&rsquo; or historical instances and mentions of placenames</li>
                <li>to associate places with their many meanings</li>
                <li>linking to source information and other datasets</li>
                <li>provide a spatio-temporal index to humanities research and culture in and about Australia</li>
                <li>provide access to this information with search and filter user interfaces, web services and visualisations and compatibility with other spatiotemporal systems</li>
            </ul>
        </li>
    </ol>
    <p>This project is supported by the Australian Research Data Commons (ARDC). The ARDC is funded by NCRIS.</p>
    <p><img style="width:300px" src="{{ asset('img/ardc_logo.png') }}" alt="ARDC logo"></p>
@endsection
