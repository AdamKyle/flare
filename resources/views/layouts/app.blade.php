<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ mix('css/tailwind.css') }}" rel="stylesheet">

    @livewireStyles

    <script src={{mix('js/manifest.js')}} type="text/javascript"></script>
    <script src={{mix('js/vendor.js')}} type="text/javascript"></script>

    <script src={{mix('js/dark-mode.js')}} type="text/javascript"></script>

    @stack('head')
</head>
@php
    $previousUrlIsInfo = strpos(url()->previous(), 'information') !== false;
@endphp

<body>
    <header class="top-bar">

        <!-- Menu Toggler -->
        @auth
            <button type="button" class="menu-toggler la la-bars" data-toggle="menu"></button>
        @endauth

        <!-- Brand -->
        <span class="brand"><a href="/">Planes of Tlessa</a></span>


        <!-- Right -->
        <div class="flex items-center absolute right-0 mr-2">

            <!-- Dark Mode -->
            @guest
                <div class="hidden  lg:contents">
                    <label class="switch switch_outlined" data-toggle="tooltip" data-tippy-content="Toggle Dark Mode">
                        <input id="darkModeToggler" type="checkbox">
                        <span></span>
                    </label>
                    <span class="ml-4">Test Dark Mode</span>

                    <a href="{{route('login')}}" class="ml-6 mr-2 text-lg">Login</a> | <a href="{{route('register')}}" class="ml-2 mr-2 text-lg">Register</a>
                </div>
            @endguest

            @auth
                <div>
                    @if (!is_null(auth()->user()->character))
                        @include('layouts.partials.player.guide-button')
                    @endif
                </div>

                <div>
                    <label class="switch switch_outlined" data-toggle="tooltip" data-tippy-content="Toggle Dark Mode">
                        <input id="darkModeToggler" type="checkbox">
                        <span></span>
                        <i class="fas fa-adjust pl-2"></i>
                    </label>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="flex items-center ltr:ml-4 rtl:mr-4 text-gray-700" data-toggle="custom-dropdown-menu"
                            data-tippy-arrow="true" data-tippy-placement="bottom-end">
                        <span class="avatar"><i class="ra ra-player"></i> </span>
                    </button>
                    <div class="custom-dropdown-menu w-64">
                        <div class="p-5">
                            <h5 class="uppercase">
                                @if (auth()->user()->hasRole('Admin'))
                                    Administrator
                                @else
                                    {{auth()->user()->character->name}}
                                @endif
                            </h5>
                            <p>
                                @if (auth()->user()->hasRole('Admin'))
                                    The Creator
                                @else
                                    Hero of Tlessa
                                @endif
                            </p>
                        </div>
                        <hr>
                        <div class="p-5">
                            <a href="{{route('user.settings', ['user' => auth()->user()->id])}}"
                               class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary">
                                <span class="la la-user-circle text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                                Settings
                            </a>
                            <a href="/information/home"
                               class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary mt-5" target="_blank">
                                <span class="la la-key text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                                Help I am stuck!
                            </a>
                        </div>
                        <hr>
                        <div class="p-5">
                            <a href="{{route('logout')}}"
                               onclick="event.preventDefault();
                                        document.getElementById('logout-form-profile').submit();"
                               class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary">
                                <span class="la la-power-off text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                                Logout
                            </a>
                        </div>

                        <form id="logout-form-profile" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </header>

    @auth
        @if(auth()->user()->hasRole('Admin'))
            @include('layouts.partials.sidebar.adminsidebar');
        @else
            @include('layouts.partials.sidebar.playersidebar');
        @endif
    @endauth

    <!-- Workspace -->
    @auth
        @if (auth()->user()->hasRole('Admin'))
            <main class="workspace scrolling-section mb-10 dark:bg-gray-900">
        @else
            <main class="workspace dark:bg-gray-900">
        @endif
    @endauth

    @guest
        <main class="workspace dark:bg-gray-900">
    @endguest

        @guest
            @include('layouts.partials.alerts')
            @yield('content')
        @endguest

        @auth
            @include('layouts.partials.alerts')
            @yield('content')
        @endauth

    </main>
    <!-- Scripts -->

    @livewireScripts

    <script src={{mix('js/theme-vendor.js')}} type="text/javascript"></script>
    <script src={{mix('js/theme-script.js')}} type="text/javascript"></script>
    <script src="{{ mix('js/app.js') }}"></script>

    @stack('scripts')
</body>
</html>
