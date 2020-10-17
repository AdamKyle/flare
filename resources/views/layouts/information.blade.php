<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha256-4+XzXVhsDmqanXGHaHvgh1gMQKX40OUvDEBTu8JcmNs="crossorigin="anonymous"></script>

    @livewireStyles

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">

        <div class="bg-light border-right" id="sidebar-wrapper">
          <div class="side-bar">
            <div class="sidebar-heading text-center">
              <h3>Information</h3>
              <span class="text-mute help-info">Help Center</span>
            </div>
            <div class="list-group list-group-flush text-right">
              <a href="{{route('info.page', [
                'pageName' => 'home'
              ])}}" class="{{$pageTitle === 'home' ? 'list-group-item list-group-item-action bg-light viewing' : 'list-group-item list-group-item-action bg-light'}}">Home</a>
              <a href="{{route('info.page', [
                'pageName' => 'character-information'
              ])}}" class="{{$pageTitle === 'character-information' ? 'list-group-item list-group-item-action bg-light viewing' : 'list-group-item list-group-item-action bg-light'}}">Character Information</a>
              <a href="{{route('info.page', [
                'pageName' => 'skill-information'
              ])}}" class="{{$pageTitle === 'skill-information' ? 'list-group-item list-group-item-action bg-light viewing' : 'list-group-item list-group-item-action bg-light'}}">Skill Information</a>
              <a href="{{route('info.page', [
                'pageName' => 'adventure'
              ])}}" class="{{$pageTitle === 'adventure' ? 'list-group-item list-group-item-action bg-light viewing' : 'list-group-item list-group-item-action bg-light'}}">Adventure</a>
            </div>
          </div>
        </div>

        <div id="page-content-wrapper">
          <header class="topbar">
            <nav class="navbar top-navbar navbar-expand-md navbar-light">
                <!-- ============================================================== -->
                <!-- Logo -->
                <!-- ============================================================== -->
                <div class="navbar-header">
                  <i class="fas fa-bars menu-toggle" id="menu-toggle"></i>
                </div>
                <!-- ============================================================== -->
                <!-- End Logo -->
                <!-- ============================================================== -->
                <div class="navbar-collapse">
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
          {{-- <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
            <i class="fas fa-bars" id="menu-toggle"></i>
          </nav> --}}
    
          <div class="container" style="margin-bottom: 100px;">
            @yield('content')
          </div>
        </div>

        <footer class="footer"> © 2020 Flare </footer>
    
    </div>

    @livewireScripts

    <script>
      $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
      });
    </script>
</body>
</html>
