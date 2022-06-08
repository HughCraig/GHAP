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
    <link href="{{ asset('/css/ben.css') }}" rel="stylesheet"> <!-- Custom css -->
    <link href="{{ asset('/css/jquery-ui.css') }}" rel="stylesheet"> <!-- JQuery UI css -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet"> <!-- Bootstrap css -->

    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> <!-- w3 Schools css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> <!-- Font awesome css -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css"> 
    
    

</head>

<body>
    <div id="app">
        <!-- NAVBAR -->
        <nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ route('index') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" 
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                            @admin
                            <li>
                                <a class="nav-link" href='/admin'>admin</a>
                            </li>
                            @endadmin
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
        <!-- End Navbar -->

        <!-- Main content area -->
        <main class="">
            @yield('content')
        </main>
    </div>
</body>

</html>