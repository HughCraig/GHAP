<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <!-- General -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

        <!-- Orbitron font 
        <link href='https://fonts.googleapis.com/css?family=Orbitron:400,700' rel='stylesheet' type='text/css'>
		-->
		
		<!-- ADDITIONS 20200314 -->
		<link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
		<!-- <link rel="stylesheet" href="/ghap/css/ghap.css"> -->
        <link rel="stylesheet" href="/ghap/css/ghap.css">
		<link rel="stylesheet" href="/css/tlcmap.css"> 
        <!--<link href="{{ asset('/css/ghap.css') }}" rel="stylesheet">  changed to use blade asset function (gets asset dir from server config) -->
		<link rel="shortcut icon" type="image/jpg" href="/favicon.ico"/>
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-144578859-1"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());

		  gtag('config', 'UA-144578859-1');
		</script>

		<!-- END ADDITIONS 20200314 -->

        <!-- TLCMap css-->
        <!-- <link href="{{ asset('/css/tlcmap.css') }}" rel="stylesheet"> -->

        <!-- Leafletjs 1.6 css -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>

        <!-- LEAFLET DRAW css -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" rel="stylesheet">

        <!-- jQuery datatables -->
        <link href="//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css" rel="stylesheet">

    <!-- Scripts -->
        <!-- jQuery 3.4.1 -->
        <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>  

         <!-- jQuery UI (downloaded to js folder)-->
        <script type="text/javascript" src="{{ asset('/js/jquery-ui.js') }}"></script>

        <!-- popper, used with bootstrap for tooltips -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

        <!-- Bootstrap 3.4 js -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script> 

        <!-- Leafletjs 1.6 js -->
        <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>

        <!-- Leaflet.draw.js -->
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

        <!-- jQuery datatables -->
        <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>

        <!-- TLCMap js-->
        <script type="text/javascript" src="{{ asset('/js/tablesort.js') }}"></script>
        <script type="text/javascript" src="{{ asset('/js/form.js') }}"></script>
        <script type="text/javascript" src="{{ asset('/js/autocomplete.js') }}" ></script>
        <script type="text/javascript" src="{{ asset('/js/urltools.js') }}" ></script>


        <link rel="shortcut icon" type="image/jpg" href="/favicon.ico"/>


    </head>










<body>
    <div id="app">
        <!-- NAVBAR -->
		
		
<div class="header">
	
	<div id="mainnav" class="">
		<a href="/"><img src="/img/tlcmaplogo.jpg" id="mainlogo" alt="Logo, dark lines on white, like several layers of mountains or a line graph." style="width:128px; display: inline-block;"></a>
		
		<div class="w3-dropdown-hover w3-mobile">
		<a href="#">TOOLS</a>
		<div class="navb w3-dropdown-content w3-bar-block w3-card-4 w3-mobile">
		  <a href="/tools/" class="w3-bar-item w3-button" style="border-left: 4px solid #FACC99; background-color: #FACC99;">Overview</a>
		  <div class="subnav">
            <a href="/ghap/" class="w3-bar-item w3-button">Gazetteer</a>
		    <a href="/quicktools/" class="w3-bar-item w3-button">Quick Tools</a>
			  
			  
		  </div>
		  <a href="/tools/#partner" class="w3-bar-item w3-button" style="border-left: 4px solid #FACC99; background-color: #FACC99;">Partner Tools</a>
		  
		  <div class="subnav">
			  <a href="/te/" target="_blank" class="w3-bar-item w3-button"> Temporal Earth</a>
			  <a href="https://heuristnetwork.org/" target="_blank" class="w3-bar-item w3-button"> Heurist</a>
			  <a href="https://huni.net.au/" target="_blank" class="w3-bar-item w3-button"> HuNI</a>
			  <a href="https://uts-eresearch.github.io/describo/" target="_blank" class="w3-bar-item w3-button">RO-Crate</a>
			  
		  </div>
		</div>
		</div>
		
		<div class="w3-dropdown-hover w3-mobile">
		<a href="#">GUIDES</a>
		<div class="navb w3-dropdown-content w3-bar-block w3-card-4 w3-mobile">
		  <a href="/guides/welcome.php" class="w3-bar-item w3-button">Beginners</a>
		  <a href="/guides/faqs.php" class="w3-bar-item w3-button">FAQs</a>
		  <a href="/guides/tutorials.php" class="w3-bar-item w3-button">Tutorials</a>
		  <a href="/guides/dev.php" class="w3-bar-item w3-button">Developers</a>
		</div>
		</div>
		
		<div class="w3-dropdown-hover w3-mobile">
		<a href="#">EXAMPLES</a>
		<div class="navb w3-dropdown-content w3-bar-block w3-card-4 w3-mobile">
		  <a href="/workflow/" class="w3-bar-item w3-button">Workflow</a>
		  <a href="/projects/" class="w3-bar-item w3-button">Projects</a>
		  <a href="/themes/" class="w3-bar-item w3-button">Themes</a>
		</div>
		</div>
		
		<div class="w3-dropdown-hover w3-mobile">
		<a href="#">ABOUT</a>
		<div class="navb w3-dropdown-content w3-bar-block w3-card-4 w3-mobile">
		  <a href="/about/devstrategy.php" class="w3-bar-item w3-button">Development</a>
		  <a href="/about/ci.php" class="w3-bar-item w3-button">Chief Investigators</a>
		  <a href="/about/partners.php" class="w3-bar-item w3-button">Partners</a>
		  <a href="/about/contact.php" class="w3-bar-item w3-button">Contact</a>
		</div>
		</div>
	</div>

	
</div>
		


<!-- Start Navbar -->
		<div class="w3-bar"  style="background-color: #17331C; color: #ffffff;" >
		
            @guest
                <a class="w3-bar-item w3-button w3-mobile" href="{{ url('/') }}" title="Search and find placenames and cultural layers.">Search</a>

            <a href="{{route('publicdatasets')}}" class="w3-bar-item w3-button w3-mobile" data-toggle="tooltip" title="Layers contributed by research community.">Browse Layers</a>
            <a href="/guides/ghap/" class="w3-bar-item w3-button w3-mobile">Help</a>
				
				
                <div class=" w3-right">
                    <a class="w3-bar-item w3-button w3-mobile" href="{{ route('login') }}">{{ __('Login') }}</a>
                
			<a class="w3-bar-item w3-button w3-mobile" href="/guides/ghap/#contribute" style="background-color:#F29469;">Create Layer</a>
                </div>
            @else
                <a class="w3-bar-item w3-button w3-mobile" href="{{ url('/') }}"  title="Search and find placenames and cultural layers.">Search</a>
                
            <a href="{{route('publicdatasets')}}" class="w3-bar-item w3-button w3-mobile" data-toggle="tooltip" title="Layers contributed by research community.">Browse Layers</a>
            <a href="/guides/ghap/" class="w3-bar-item w3-button w3-mobile">Help</a>
                 <!--Logout-->
                 <div class=" w3-mobile w3-right">
                    <a class="w3-bar-item w3-button w3-mobile" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('Logout') }}
                    </a>
		<a class="w3-bar-item w3-button w3-mobile" href="{{url('myprofile/mydatasets/newdataset')}}" style="background-color:#F29469;">Create Layer</a>
                </div>

                <!-- MyProfile -->
                <div class=" w3-dropdown-hover w3-mobile w3-right">
                    <a href="{{url('myprofile')}}" class="nav-link-but"><button class="w3-button"> Account ({{ substr(Auth::user()->name, 0, 16) }}) <i class="fa fa-caret-down"></i></button></a>
                    <div class="w3-dropdown-content w3-bar-block w3-dark-grey">
                        <a href="{{url('myprofile/mydatasets')}}" class="w3-bar-item w3-button w3-mobile">My Layers</a>
                        <a href="{{url('myprofile/mysearches')}}" class="w3-bar-item w3-button w3-mobile">My Searches</a>
                        <a href="{{url('myprofile/mydatasets')}}" class="w3-bar-item w3-button w3-mobile">My Collaborators</a>    
                    </div>
                </div>

                @admin
                    <!-- Admin -->
                    <div class=" w3-dropdown-hover w3-mobile w3-right">
                        <a class="nav-link-but" href='/admin'><button class="w3-button">Admin <i class="fa fa-caret-down"></i></button></a>
                        <div class="w3-dropdown-content w3-bar-block w3-dark-grey">
                            <a href="{{url('admin/users')}}" class="w3-bar-item w3-button w3-mobile">Manage Users</a>
                            <a href="{{url('admin')}}" class="w3-bar-item w3-button w3-mobile">ABC</a>
                            <a href="{{url('admin')}}" class="w3-bar-item w3-button w3-mobile">123</a>    
                        </div>
                    </div>
                @endadmin
               
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
            <div class="notification" id="notification_box"><span id="notification_message" class="align-middle notification_message"></span></div>
        </main>
    </div>

<!-- TLCMAP FOOTER -->



<!-- begin footer -->

<div class="footer">
<img src="/img/footmnt.png"><img src="/img/foottile.png">
</div>
<script src="/js/tlcmapnav.js"></script>


</body>
</html>
