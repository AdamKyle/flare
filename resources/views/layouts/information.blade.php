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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />

    @vite('resources/css/tailwind.css')

    @livewireStyles

    @vite('resources/js/vendor/livewire-data-tables.js')

    @vite('resources/js/vendor/livewire.js')

    @vite('resources/vendor/theme/assets/js/dark-mode/dark-mode.js')

    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>

    @stack('head')
</head>
@php
    $previousUrlIsInfo = strpos(url()->previous(), 'information') !== false;
@endphp

<body>
<!-- Top Bar -->
<header class="top-bar">

    <!-- Menu Toggler -->
    <button type="button" class="menu-toggler la la-bars" data-toggle="menu"></button>

    <!-- Brand -->
    <span class="brand"><a href="/">Planes of Tlessa</a></span>


    <!-- Right -->
    <div class="flex items-center absolute right-0 mr-2 pb-5">

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
    </div>
</header>

@include('layouts.partials.sidebar.informationsidebar')

<!-- Workspace -->
<main class="workspace">

    @guest
        @include('layouts.partials.alerts')

        @yield('content')
    @endguest

    @auth
        @include('layouts.partials.alerts')

        @yield('content')
    @endauth

</main>

@livewireScriptConfig

@vite('resources/js/vendor/theme-script.js')


<script>
    const lightbox = GLightbox();
</script>

@stack('scripts')
</body>
</html>
