<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
    </header>

    <!-- Workspace -->
    <main class="workspace dark:bg-gray-700">

        @guest
            <div>
                @include('layouts.partials.alerts')
                @yield('content')
            </div>
        @endguest

        @auth
            <div>
                <div>
                    @include('layouts.partials.alerts')
                    @yield('content')
                </div>
            </div>
        @endauth

    </main>

    <script src={{mix('js/theme-vendor.js')}} type="text/javascript"></script>
    <script src={{mix('js/theme-script.js')}} type="text/javascript"></script>
    <script src="{{ mix('js/app.js') }}"></script>

    @stack('scripts')
</body>
</html>
