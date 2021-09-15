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

    @guest
    @else
        <meta name="player" content="{{ auth()->user()->id }}">

        @if (!auth()->user()->hasRole('Admin'))
            <meta name="character" content="{{ auth()->user()->character->id}}">
        @endif
    @endguest

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet">

    @livewireStyles
    <script src={{mix('js/manifest.js')}} type="text/javascript"></script>
    <script src={{mix('js/vendor.js')}} type="text/javascript"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha256-4+XzXVhsDmqanXGHaHvgh1gMQKX40OUvDEBTu8JcmNs="crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
    <script src={{mix('js/kingdom-unit-movement.js')}} type="text/javascript"></script>
    <script src={{mix('js/character-boons.js')}} type="text/javascript"></script>
    <script src="https://unpkg.com/echarts/dist/echarts.min.js"></script>
    <script src="https://unpkg.com/@chartisan/echarts/dist/chartisan_echarts.js"></script>

    @stack('head')
</head>
@php
    $previousUrlIsInfo = strpos(url()->previous(), 'information') !== false;
@endphp

<body class="fix-header fix-sidebar card-no-border {{!$previousUrlIsInfo ? 'mini-sidebar' : ''}}">
    <div id="main-wrapper">
        <div id="app">
            <header class="topbar">
                <nav class="navbar top-navbar navbar-expand-md navbar-light">
                    <div class="navbar-collapse">
                        @guest
                        @else
                            <!-- ============================================================== -->
                            <!-- toggle and nav items -->
                            <!-- ============================================================== -->
                            <ul class="navbar-nav mr-auto mt-md-0 ">
                                <li class="nav-item">
                                    <a class="nav-link sidebartoggler text-muted" href="javascript:void(0)">
                                        <i class="fas fa-bars"></i>
                                    </a>
                                </li>
                            </ul>
                        @endguest
                        <!-- ============================================================== -->
                        <!-- User profile -->
                        <!-- ============================================================== -->
                        @guest
                        @else
                            @include('layouts.partials.user-profile-nav')
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

            @guest
            @else
                @if ($previousUrlIsInfo)
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
                @else
                    <aside class="left-sidebar">
                        <!-- Sidebar scroll-->
                        <div class="scroll-sidebar">
                            <!-- Sidebar navigation-->
                            <nav class="sidebar-nav">
                                @if (auth()->user()->hasRole('Admin'))
                                    @include('layouts.partials.sidebar.adminsidebar')
                                @else
                                    @include('layouts.partials.sidebar.playersidebar')
                                @endif
                            </nav>
                            <!-- End Sidebar navigation -->
                        </div>
                        <!-- End Sidebar scroll-->
                    </aside>
                @endif
            @endguest

            @guest
                <div class="container-fluid" style="min-height: 853px;">
                    @include('layouts.partials.alerts')
                    @yield('content')
                </div>

                <footer class="footer" style="left: 0;"> © 2020 Flare </footer>
            @else
                <div class="page-wrapper">
                    <div class="container-fluid mb-5">

                        @if(!auth()->user()->hasRole('Admin'))
                            <div id="refresh"></div>
                        @endif

                        @include('layouts.partials.alerts')
                        @yield('content')
                    </div>
                </div>

                <footer class="footer"></footer>
            @endif
        </div>
    </div>

    <!-- Scripts -->

    @livewireScripts

    <script src="{{ mix('js/app.js') }}"></script>

    @stack('scripts')
</body>
</html>
