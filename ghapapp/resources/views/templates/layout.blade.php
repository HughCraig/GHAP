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

    <!-- TLCMap js-->
    <script type="text/javascript" src="{{ asset('/js/tablesort.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/autocomplete.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/urltools.js') }}"></script>

</head>

<body>
<div id="app">
    <!-- NAVBAR -->
    <div class="header">

        <div id="mainnav" class="main-nav">
            <div class="main-site-logo">
                <a href="{{ url('/') }}">
                    @include('templates/misc/ghap_logo')
                </a>
            </div>

            <div class="main-nav-content">
                <div class="site-name">
                    Gazetteer of Historical Australian Places
                </div>
                <div class="main-menu">
                    <div class="w3-dropdown-hover" style="display:none;"></div>
                    <div class="main-menu-item w3-dropdown-hover w3-mobile">
                        <a href="#">Browse Layers <i class="fa fa-caret-down"></i></a>
                        <div class="navb w3-dropdown-content w3-bar-block w3-card-4 w3-mobile">
                            <a href="{{ url('publicdatasets') }}" class="w3-bar-item w3-button">Layers</a>
                            <a href="{{ url('publiccollections') }}" class="w3-bar-item w3-button">Multilayers</a>
                        </div>
                    </div>
                    <div class="main-menu-item">
                        <a href="#">About</a>
                    </div>
                    <div class="main-menu-item w3-dropdown-hover w3-mobile">
                        <a href="#">Help <i class="fa fa-caret-down"></i></a>
                        <div class="navb w3-dropdown-content w3-bar-block w3-card-4 w3-mobile">
                            <a href="#" class="w3-bar-item w3-button">Get started</a>
                            <a href="https://tlcmap.org/guides/ghap/" class="w3-bar-item w3-button">Guide</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="secondary-site-logo">
                <a href="https://tlcmap.org/">
                    @include('templates/misc/tlcmap_logo')
                </a>
            </div>
        </div>
    </div>

    <!-- Start Navbar -->
    <div class="secondary-nav w3-bar">
        @guest
            <div class=" w3-mobile w3-right">
                <a class="w3-bar-item w3-button w3-mobile" href="{{ url('login') }}">Log in</a>
            </div>
        @else
            <a class="w3-bar-item w3-button w3-mobile" href="{{ url('myprofile') }}">My profile ({{ Auth::user()->name }})</a>
            <a class="w3-bar-item w3-button w3-mobile" href="{{ url('myprofile/mydatasets') }}">My layers</a>
            <a class="w3-bar-item w3-button w3-mobile" href="{{ url('myprofile/mycollections') }}">My multilayers</a>
            <a class="w3-bar-item w3-button w3-mobile" href="{{ url('myprofile/mysearches') }}">My searches</a>
            {{-- Custom directive 'admin' to check wether the user has the admin role. --}}
            @admin
                <a class="w3-bar-item w3-button w3-mobile" href="{{ url('admin') }}">Admin</a>
            @endadmin
            <div class=" w3-mobile w3-right">
                <a class="w3-bar-item w3-button w3-mobile" href="{{ url('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Log out</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
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

<!-- Other JS -->
@stack('scripts')

</body>
</html>
