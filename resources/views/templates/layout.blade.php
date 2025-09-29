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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

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

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XDYZQTNPQP"></script> 
    <script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'G-XDYZQTNPQP'); </script>

    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'G-XDYZQTNPQP');
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
    <script type="text/javascript" src="{{ asset('/js/form.js') }}"></script>

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
                <div class="site-sub-name">
                    We acknowledge the Traditional Owners of country and pay our respects to Elders past, present and emerging. <a href="{{ config('app.tlcmap_doc_url') }}/first-australians/" target="_blank" style="text-decoration: underline !important; color: #17331C;">First Australians...</a>
                </div>
          </div>

        </div>

            


<!-- Start new navbar -->


<nav class="navbar navbar-expand-lg tlcmgreen border-bottom" data-bs-theme="dark">
  <div class="container-fluid">

    <!-- Collapsible content (all items live here) -->
    <div class="collapse navbar-collapse order-3 order-lg-1 tlcmgreen" id="greennav">
      <!-- Left: public menu -->
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="{{ url('layers') }}">Layers</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ url('multilayers') }}">Multilayers</a></li>
        <li class="nav-item"><a class="nav-link" href="{{ url('contribute') }}">Add To Map</a></li>
      </ul>

      <!-- Right: logged in menu -->
      
      
      






<ul class="navbar-nav ms-auto align-items-lg-center">
  

  @guest
    {{-- Guest: plain links (no dropdown) --}}
    <li class="nav-item">
      <a class="nav-link" href="{{ url('register') }}">Register</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="{{ url('login') }}">Log in</a>
    </li>
  @else
    {{-- Auth: My Maps menu --}}
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" role="button"
         data-bs-toggle="dropdown" aria-expanded="false">
        My Maps
      </a>
      <ul class="dropdown-menu dropdown-menu-end tlcmgreen">
        <li><a class="dropdown-item" href="{{ url('myprofile/mydatasets') }}">My layers</a></li>
        <li><a class="dropdown-item" href="{{ url('myprofile/mycollections') }}">My multilayers</a></li>
        <li><a class="dropdown-item" href="{{ url('myprofile/mytexts') }}">My texts</a></li>
        <li><a class="dropdown-item" href="{{ url('myprofile/mysearches') }}">My searches</a></li>
      </ul>
    </li>


    {{-- Auth: User menu --}}
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" role="button"
         data-bs-toggle="dropdown" aria-expanded="false">
        {{ Auth::user()->name }}
      </a>
      <ul class="dropdown-menu dropdown-menu-end tlcmgreen">
        <li><a class="dropdown-item" href="{{ url('myprofile') }}">My profile</a></li>
        @admin
          <li><a class="dropdown-item" href="{{ url('admin') }}">Admin</a></li>
        @endadmin
        <li><hr class="dropdown-divider"></li>
        <li>
          <a class="dropdown-item" href="{{ url('logout') }}"
             onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Log out
          </a>
        </li>
      </ul>
    </li>


  {{-- Always visible --}}
  <li class="nav-item">
    <a class="nav-link" href="{{ config('app.tlcmap_doc_url') }}/contact/">Contact</a>
  </li>
  <li class="nav-item d-lg-none">
    <a class="nav-link" href="https://docs.tlcmap.org/help/guides/guide/">Help</a>
  </li>
  <li class="nav-item d-lg-none">
    <a class="nav-link" href="https://docs.tlcmap.org">About</a>
  </li>

  @endguest

  {{-- Desktop burger → opens #moreMenu offcanvas --}}
  <li class="nav-item d-none d-lg-block">
    <button class="btn nav-link p-2" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#moreMenu"
            aria-controls="moreMenu" aria-label="More">
      <span class="navbar-toggler-icon"></span>
    </button>
  </li>
</ul>






      
      <!--
      !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            My Maps
          </a>
		  <ul class="dropdown-menu dropdown-menu-end tlcmgreen">
            <li><a class="dropdown-item" href="{{ url('myprofile/mydatasets') }}">My layers</a></li>
            <li><a class="dropdown-item" href="{{ url('myprofile/mycollections') }}">My multilayers</a></li>
            <li><a class="dropdown-item" href="{{ url('myprofile/mytexts') }}">My texts</a></li>
			<li><a class="dropdown-item" href="{{ url('myprofile/mysearches') }}">My searches</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown me-lg-2">
            @guest
            <ul class="dropdown-menu dropdown-menu-end tlcmgreen">
            <li><a class="dropdown-item" href="{{ url('register') }}">Register</a></li>
            <li><a class="dropdown-item" href="{{ url('login') }}">Log in</a></li>
            </ul>
            @else


        
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            {{ Auth::user()->name }}
          </a>
          <ul class="dropdown-menu dropdown-menu-end tlcmgreen">
            <li><a class="dropdown-item" href="{{ url('myprofile') }}">My profile</a></li>
            @admin
            <li><a class="dropdown-item" href="{{ url('admin') }}">Admin</a></li>
            @endadmin
            <li><a class="dropdown-item" href="{{ url('logout') }}" onclick="event.preventDefault();
					document.getElementById('logout-form').submit();">Log out</a>
          </ul>

          @endguest

        </li>

        <!-- Small-screen burger menu, right  
        <li class="nav-item dropdown d-lg-none">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More">
            Help
          </a>
          <ul class="dropdown-menu dropdown-menu-end tlcmgreen">
            <li><a class="dropdown-item" href="{{ config('app.tlcmap_doc_url') }}/help/guides/guide/">Guide</a></li>
            <li><a class="dropdown-item" href="{{ config('app.tlcmap_doc_url') }}">About</a></li>
          </ul>
        </li>

        <!-- Large screen burger with its own dropdown, far right ... see offcanvas menu 
        <li class="nav-item d-none d-lg-block">
		  <button class="btn nav-link p-2" type="button"
				  data-bs-toggle="offcanvas" data-bs-target="#moreMenu"
				  aria-controls="moreMenu" aria-label="More">
			<span class="navbar-toggler-icon"></span>
		  </button>
		</li>
      </ul>


      -->
	  
	  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
	  
    </div>

    <!-- Small-screen toggler to open/close everything -->
    <button class="navbar-toggler ms-auto order-2" type="button" data-bs-toggle="collapse"
        data-bs-target="#greennav" aria-controls="greennav" aria-expanded="false"
        aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    
  </div>
</nav>		
			

<!-- Main Offcanvas menu (right side) -->

<!-- Main Offcanvas menu (right side) -->
<div class="offcanvas offcanvas-end" data-bs-theme="light"
     tabindex="-1" id="moreMenu" 
     aria-labelledby="moreMenuLabel" >
  <div class="offcanvas-header border-bottom">
    <h5 id="moreMenuLabel" class="mb-0"><a href="{{ url('/') }}" style="color: #000;">Home</a></h5>
    <button type="button" class="btn-close"
            data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body p-0">
    <div class="accordion accordion-flush" id="moreAccordion">

      <!-- Group 1 -->
      <div class="accordion-item ">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#moreGroup1"
                  aria-expanded="false">TOOLS</button>
        </h2>
        <div id="moreGroup1" class="accordion-collapse collapse" data-bs-parent="#moreAccordion">
          <div class="accordion-body p-0">
            <ul class="list-unstyled mb-0">
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/help/guides/guide/">Add To Map</a></li>
              <li><a class="dropdown-item py-2 px-3" href="https://quicktools.tlcmap.org/">Quick Tools</a></li>
			  <li><a class="dropdown-item py-2 px-3" href="https://www.researchobject.org/ro-crate/">RO-Crate</a></li>
			  <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/core-data/">Core Data</a></li>
			  <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/help/developers/">Web Services</a></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Group 2 -->
      <div class="accordion-item ">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#moreGroup2"
                  aria-expanded="false">Help</button>
        </h2>
        <div id="moreGroup2" class="accordion-collapse collapse" data-bs-parent="#moreAccordion">
          <div class="accordion-body p-0">
            <ul class="list-unstyled mb-0">
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/help/">Get started</a></li>
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/help/guides/">Guides</a></li>
			  <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/help/faqs/">FAQs</a></li>
			  <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/help/developers/">Developers</a></li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Group 3 -->
      <div class="accordion-item ">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#moreGroup3"
                  aria-expanded="false">About</button>
        </h2>
        <div id="moreGroup3" class="accordion-collapse collapse" data-bs-parent="#moreAccordion">
          <div class="accordion-body p-0">
            <ul class="list-unstyled mb-0">
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/first-australians/">First Australians</a></li>
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/about/updates/">Updates</a></li>
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/about/lead-researchers/">Lead researchers</a></li>
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/about/partners/">Partners</a></li>
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/research-outputs/">Research Outputs</a></li>
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/examples/">Examples</a></li>
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/contact/">Contact</a></li>
              <li><a class="dropdown-item py-2 px-3" href="{{ config('app.tlcmap_doc_url') }}/about/conditionsofuse/">Conditions of Use</a></li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<!-- End offcanvas menu -->
<!-- End new navbar -->

<!-- end header section -->
</div>



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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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