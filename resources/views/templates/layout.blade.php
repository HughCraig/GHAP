<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- General -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/jpg" href="/favicon.ico"/>

    <!-- Title -->
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!--
        TLCMap was concieved by Bill Pascoe System Architect with the support of Professor Hugh Craig.
    Developers: Bill Pascoe, Ben McDonnell, Kaine Usher, Zongwen Fan
    The Chief Investigators and Partners listed in the grants, and the steering committee and advisory board members provided valuable use cases and proof of concept to demonstrate feasibility and usefulness, essential for prioritising features and development.
    TLCMap v1 was funded by the Australian Research Council, PROJECT ID: LE190100019 (2019 - 2020)
    TLCMap v2 was funded by the Australian Research Data Commons. (2021)
    These grants were administered by the lead institution on the applications, the University of Newcastle, Australia, and other participating insitutions.
    -->

    <!-- css -->
    <!-- JQuery UI css -->
    <link href="{{ asset('/css/jquery-ui.css') }}" rel="stylesheet">

    <!-- Bootstrap css -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <!-- w3 Schools css -->
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

    <!-- Font awesome css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Bootstrap min css-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">

    <!-- Google font -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">

    <!-- GHAP CSS -->
    <link rel="stylesheet" href="{{ asset('css/ghap.css') }}">

    <!-- TLCMap CSS -->
    <link rel="stylesheet" href="{{ asset('css/tlcmap_base.css') }}">

    <!-- Leafletjs 1.6 css -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css"
          integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
          crossorigin=""/>

    <!-- LEAFLET DRAW css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" rel="stylesheet">

    <!-- jQuery datatables -->
    <link href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" rel="stylesheet">

    <!-- ArcGis styles -->
    <link rel="stylesheet" href="https://js.arcgis.com/4.26/esri/themes/light/main.css">

    <!-- Other CSS -->
    @stack('styles')

    <!-- Scripts -->

    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-144578859-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'UA-144578859-1');
    </script>

    <!-- jQuery 3.4.1 -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>

    <!-- jQuery UI (downloaded to js folder)-->
    <script type="text/javascript" src="{{ asset('/js/jquery-ui.js') }}"></script>

    <!-- popper, used with bootstrap for tooltips -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
            integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
            crossorigin="anonymous"></script>

    <!-- Bootstrap 3.4 js -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>

    <!-- Leafletjs 1.6 js -->
    <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js"
            integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew=="
            crossorigin=""></script>

    <!-- Leaflet.draw.js -->
    <script type="text/javascript"
            src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

    <!-- jQuery datatables -->
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>

    <!-- TinyMCE -->
    <script src="{{ asset('tinymce/tinymce.min.js') }}"></script>

    <!-- TLCMap js-->
    <script type="text/javascript" src="{{ asset('/js/tablesort.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/tooltips.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/autocomplete.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/urltools.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/help-video.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/wysiwyg-editor.js') }}"></script>

</head>

<body>
<div id="app">
    @if (config('app.env') !== 'production')
        <!-- Non-production site warning -->
        <div class="site-warning">
            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
            This site is for testing only.  Don’t upload valuable research as testing data will not be maintained.
        </div>
    @endif
    <!-- NAVBAR -->
    <div class="header">

        <div id="mainnav" class="main-nav">
            <div class="main-site-logo">
                <a href="/">
                    @include('templates/misc/tlcmap_logo')
                </a>
            </div>

            <div class="main-nav-content">
                <div class="site-name">
                    Mapping Australian history and culture
                </div>
                <div class="site-name" style="font-size: 0.8em; margin-bottom:0">
                    We acknowledge the Traditional Owners of country and pay our respects to Elders past, present and emerging. <a href="{{ config('app.tlcmap_doc_url') }}/first-australians/" target="_blank" style="text-decoration: underline !important;">First Australians...</a>
                </div>
            </div>

            <div class="menu-nav-button">
                <span class="menu-nav-button-icon">
                    <span class="icon icon-menu-dark">
                    </span>
                </span>
            </div>
        </div>

            <nav class="menu-nav">
                <div class="menu-nav-close">
                    <span class="icon icon-close-dark"></span>
                </div>
                <ul>
                    <li>
                        <div style="display:flex; font-weight:bold"><a href="{{ url('/') }}" style="color:black">HOME</a></div>
                    </li>
                    <li>
                        <div style="display:flex; font-weight:bold"><a href="{{ config('app.tlcmap_doc_url') }}/tools/" style="color:black">TOOLS</a><span id="menu-item-58" class="icon icon-arrow-down-dark u-pull-right submenu-toggle"></span></div>
                        <ul class="submenu" style="display: block;">
                            <li><a href="https://quicktools.tlcmap.org/">Quick Tools</a></li>
                            <li><a href="https://www.researchobject.org/ro-crate/">RO-Crate</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/core-data/">Core Data</a></li>
                        </ul>
                    </li>
                    <li>
                        <div style="display:flex; font-weight:bold"><a href="{{ config('app.tlcmap_doc_url') }}/help/" style="color:black">HELP</a><span id="menu-item-60" class="icon icon-arrow-down-dark u-pull-right submenu-toggle"></span></div>
                        <ul class="submenu" style="display: block;">
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/help/">Get started</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/help/guides/">Guides</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/help/faqs/">FAQs</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/help/developers/">Developers</a></li>
                        </ul>
                    </li>
                    <li>
                        <div style="display:flex; font-weight:bold"><a href="{{ config('app.tlcmap_doc_url') }}/about/" style="color:black">ABOUT</a><span id="menu-item-59" class="icon icon-arrow-down-dark u-pull-right submenu-toggle"></span></div>
                        <ul class="submenu" style="display: block;">
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/first-australians/">First Australians</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/about/updates/">Updates</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/about/lead-researchers/">Lead researchers</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/about/partners/">Partners</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/examples/">Examples</a></li>
                            <li><a href="{{ config('app.tlcmap_doc_url') }}/contact/">Contact</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Start Navbar -->
        <div class="secondary-nav w3-bar" style="padding-left: 1%; padding-right: 1%;">     
            <div class="w3-mobile w3-left">
                    <a class="w3-bar-item w3-button w3-mobile" href="{{ url('layers') }}">Layers</a>
                </div>
            <div class="w3-mobile w3-left">
                <a class="w3-bar-item w3-button w3-mobile" href="{{ url('multilayers') }}">Multilayers</a>
            </div>    

            @guest
            <div class="w3-mobile w3-right">
                <a class="w3-bar-item w3-button w3-mobile" href="{{ url('login') }}">Log in</a>
            </div>
            <div class="w3-mobile w3-right">
                <a class="w3-bar-item w3-button w3-mobile" href="{{ url('register') }}">Register</a>
            </div>
            @else
            <div class="w3-mobile w3-right">
                <a class="w3-bar-item w3-button" href="{{ url('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Log out</a>
            </div>

            <div class="w3-dropdown-hover w3-mobile w3-right">
                <button class="w3-button" style=" background-color: #17331C;color: white;">
                    {{ Auth::user()->name }} <i class="fa fa-caret-down"></i>
                </button>
                <div class="w3-dropdown-content w3-bar-block w3-card-4">
                    <a class="w3-bar-item w3-button" href="{{ url('myprofile') }}">My profile</a>
                    <a class="w3-bar-item w3-button" href="{{ url('myprofile/mydatasets') }}">My layers</a>
                    <a class="w3-bar-item w3-button" href="{{ url('myprofile/mycollections') }}">My multilayers</a>
                    <a class="w3-bar-item w3-button" href="{{ url('myprofile/mysearches') }}">My searches</a>
                    @admin
                    <a class="w3-bar-item w3-button" href="{{ url('admin') }}">Admin</a>
                    @endadmin
                </div>
            </div>


            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            @endguest

        </div>

        <!-- End Navbar -->

    <!-- Main content area -->
    <main class="w3-container tlcmapcontainer">

        <!-- Top of page messages -->
        @if(session()->has('success'))
            <div class="alert alert-success mb-0">
                {{ session()->get('success') }}
            </div>
        @endif
        @if(session()->has('warning'))
            <div class="alert alert-warning mb-0">
                {{ session()->get('warning') }}
            </div>
        @endif
        @if(session()->has('error'))
            <div class="alert alert-danger mb-0">
                {{ session()->get('error') }}
            </div>
        @endif
        @if(session()->has('exception'))
            <div class="alert alert-warning mb-0">
                {{ session()->get('exception') }}
            </div>
        @endif
        @if(session()->has('message'))
            <div class="alert alert-secondary mb-0">
                {{ session()->get('message') }}
            </div>
        @endif

        @yield('content')

        <!-- Bottom Notification Box -->
        <div class="notification" id="notification_box">
            <span id="notification_message" class="align-middle notification_message"></span>
        </div>
    </main>
</div>

<!-- TLCMAP FOOTER -->

<!-- begin footer -->

<div class="footer">
    <img src="{{ asset('img/footmnt.png') }}"><img src="{{ asset('img/foottile.png') }}">
</div>

@if (!empty(config('app.help_video_url')))
    <!-- GHAP help video modal -->
    <div id="helpVideoModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content position-relative">
                <!-- Close button -->
                    <button type="button" 
                            class="btn btn-close" 
                            data-dismiss="modal" 
                            aria-label="Close">
                            <i class="fa fa-times"></i>
                    </button>
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe class="embed-responsive-item" src="{{ config('app.help_video_url') }}" title="GHAP Help Video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Other JS -->
@stack('scripts')

<!-- ArcGIS Maps SDK for JavaScript -->
<!-- This must be included at the last position as it has conflicts with jQuery UI and widgets -->
<script type="module" src="https://js.arcgis.com/calcite-components/2.5.1/calcite.esm.js"></script>
<script src="https://js.arcgis.com/4.26/"></script>
</body>
</html>