<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Language" content="en">
    <meta name="google" content="notranslate">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">

    @livewireStyles

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha256-4+XzXVhsDmqanXGHaHvgh1gMQKX40OUvDEBTu8JcmNs="crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
    <script src="{{ asset('js/vendor.js') }}"></script>
    <script src="{{ asset('js/manifest.js') }}"></script>
</head>
<body class="fix-header fix-sidebar card-no-border">
    <div id="main-wrapper">
        <div id="app">
            <header class="topbar">
                <nav class="navbar top-navbar navbar-expand-md navbar-light">
                    <div class="navbar-collapse">
                        <!-- ============================================================== -->
                        <!-- toggle and nav items -->
                        <!-- ============================================================== -->
                        <ul class="navbar-nav mr-auto mt-md-0 info-sidebar-toggler">
                          <li class="nav-item">
                              <a class="nav-link infoSidebarToggler text-muted" href="javascript:void(0)" onclick="showSideBar()">
                                  <i class="fas fa-bars"></i>
                              </a>
                          </li>
                        </ul>
                        <!-- ============================================================== -->
                        <!-- User profile -->
                        <!-- ============================================================== -->
                        @guest
                        @else
                        <div style="position: absolute; right: 10px;">
                            @include('layouts.partials.user-profile-nav')
                        </div>
                        @endguest

                        <ul class="navbar-nav my-lg-0 text-align-right force-right">
                            <!-- Authentication Links -->
                            @guest
                                <li class="nav-item">
                                    <a class="nav-link" href="/">{{ __('Home') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                    </li>
                                @endif
                            @endguest
                        </ul>
                    </div>
                </nav>
            </header>

            <aside class="left-sidebar info-sidebar" id="info-left-sidebar">
              <!-- Sidebar scroll-->
              <div class="scroll-sidebar">
                  <!-- Sidebar navigation-->
                  <nav class="sidebar-nav info-nav">
                    @include('layouts.partials.sidebar.informationsidebar')
                  </nav>
                  <!-- End Sidebar navigation -->
              </div>
          </aside>

          <div class="page-wrapper page p-5">
            <div class="container mb-5 info-text">
                @yield('content')
            </div>
        </div>

            <footer class="footer"></footer>
        </div>
    </div>

    <!-- Scripts -->

    @livewireScripts

    <script src="{{ asset('js/manifest.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>

    <script>
        function showSideBar() {
            foundSideBar = document.querySelector('#info-left-sidebar');
            foundSideBar.classList.toggle('showSidebar');
        }

        var lightbox = GLightbox();

        lightbox.on('open', (target) => {
            // do nothing ...
        });
    </script>

    @stack('scripts')
</body>
</html>
