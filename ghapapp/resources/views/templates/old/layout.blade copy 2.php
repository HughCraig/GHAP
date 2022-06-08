<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- General -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Scripts -->
    <script type="text/javascript" src="{{ asset('/js/tablesort.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/autocomplete.js') }}" ></script>

    <script type="text/javascript" src="http://code.jquery.com/jquery-latest.js"></script> <!-- jQuery library CDN -->
    <script type="text/javascript" src="{{ asset('/js/jquery-ui.js') }}"></script> <!-- jQuery UI (downloaded to js folder)-->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script> <!-- Bootstrap js library CDN -->

    <!-- css -->
    <link href="{{ asset('/css/jquery-ui.css') }}" rel="stylesheet"> <!-- JQuery UI css -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet"> <!-- Bootstrap css -->

    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> <!-- w3 Schools css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> <!-- Font awesome css -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css"> 

    <link href='https://fonts.googleapis.com/css?family=Orbitron:400,700' rel='stylesheet' type='text/css'><!-- Orbitron font -->

    <link href="{{ asset('/css/tlcmap.css') }}" rel="stylesheet"> <!-- Custom css for tlcmap-->
    
    

</head>

<body>
    <div id="app">
        <!-- NAVBAR -->
        <div class="w3-container w3-orange header" style="">
             <a href="{{url('ws/ghap')}}" class="">
            <img src="{{ asset('/img/tlcmaplogo_13b.jpg') }}" alt="Logo line and circle on orange, at the same time an eye, a hill, river and water hole, or lines from a topographic map"  style="height:53px; float: left;display: inline-block; padding-right: 16px;"></a>
            <h1 class="m-4"><a href="{{url('ws/ghap')}}" >{{ config('app.name', 'Laravel') }}</a></h1>  
        </div>
        <div class="w3-bar w3-black" id="mainnav">
            @guest
                <a class=" w3-bar-item w3-button w3-mobile" href="{{ route('login') }}">{{ __('Login') }}</a>
                @if (Route::has('register'))
                    <a class="w3-bar-item w3-button w3-mobile" href="{{ route('register') }}">{{ __('Register') }}</a>
                @endif
                <a class="w3-bar-item w3-button w3-mobile" href='#'>FAQs</a>
                <a class="w3-bar-item w3-button w3-mobile" href='#'>About</a>
                <a class="w3-bar-item w3-button w3-mobile" href='#'>Contact</a>
            @else
            <div class="w3-black w3-dropdown-hover w3-mobile">
                <a href="{{url('myprofile')}}" class="nav-link-but"><button class="w3-button"> Account <i class="fa fa-caret-down"></i></button></a>
                <div class="w3-dropdown-content w3-bar-block w3-dark-grey">
                    <a href="{{url('myprofile/mydatasets')}}" class="w3-bar-item w3-button w3-mobile">My Datasets</a>
                    <a href="{{url('myprofile/mysearches')}}" class="w3-bar-item w3-button w3-mobile">My Searches</a>
                    <a href="{{url('myprofile/mydatasets')}}" class="w3-bar-item w3-button w3-mobile">My Collaborators</a>    
                </div>
            </div>
                @admin
                    <div class="w3-black w3-dropdown-hover w3-mobile">
                        <a class="nav-link-but" href='/admin'><button class="w3-button">Admin <i class="fa fa-caret-down"></i></button></a>
                        <div class="w3-dropdown-content w3-bar-block w3-dark-grey">
                            <a href="{{url('admin/users')}}" class="w3-bar-item w3-button w3-mobile">Manage Users</a>
                            <a href="{{url('admin')}}" class="w3-bar-item w3-button w3-mobile">ABC</a>
                            <a href="{{url('admin')}}" class="w3-bar-item w3-button w3-mobile">123</a>    
                        </div>
                    </div>
                @endadmin
                <a class="w3-bar-item w3-button w3-mobile" href='#'>FAQs</a>
                <a class="w3-bar-item w3-button w3-mobile" href='#'>About</a>
                <a class="w3-bar-item w3-button w3-mobile" href='#'>Contact</a>

                <!--Logout-->
                <a class="w3-bar-item w3-button w3-mobile" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    {{ __('Logout') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            @endguest
        </div>
        <!-- End Navbar -->

        <!-- Main content area -->
        <main class="w3-container tlcmapcontainer">
            @yield('content')
        </main>
    </div>
</div>

<!-- begin footer -->
<footer class="fixed-bottom w3-container w3-amber w3-leftbar w3-border w3-border-orange w3-center">
    <p>Time Layered Cultural Map is funded by the Australian Research Council, PROJECT ID: LE190100019
        <a href="https://www.arc.gov.au/news-publications/media/research-highlights/australian-cultural-and-historical-data-be-linked-new-research-infrastructure">
            <img src="{{ asset('/img/arclogo.png') }}" style="height:50px;"></a>
        <a href="https://c21ch.newcastle.edu.au/">
            <img src="{{ asset('/img/21CHUM_Logo_Extend.jpg') }}" style="height:50px;"></a>
        <a href="https://www.newcastle.edu.au">
            <img src="{{ asset('/img/logo-uon.png') }}" style="height:50px;"></a>
    </p>
</footer>

</body>

</html>