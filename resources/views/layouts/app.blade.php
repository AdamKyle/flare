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

    @vite('resources/sass/app.scss')
    @vite('resources/css/tailwind.css')

    @livewireStyles

    @vite('resources/js/vendor/livewire-data-tables.js')

    @vite('resources/js/vendor/livewire.js')

    @vite('resources/vendor/theme/assets/js/dark-mode/dark-mode.js')

    @stack('head')
</head>
@php
    $previousUrlIsInfo = strpos(url()->previous(), 'information') !== false;
@endphp

<body>
    @guest
        <header class="top-bar hidden md:block">
    @else
        <header class="top-bar">
    @endif

        <!-- Menu Toggler -->
        @auth
            <button type="button" class="menu-toggler la la-bars" data-toggle="menu"></button>
        @endauth

        <!-- Brand -->
        @guest
            <span class="brand relative top-[20px] hidden md:block"><a href="/">Planes of Tlessa</a></span>
        @else
            <span class="brand hidden md:block"><a href="/">Planes of Tlessa</a></span>
        @endguest


        <!-- Right -->
        <div class="flex items-center absolute right-0 mr-2">

            <!-- Dark Mode -->
            @guest
                <div class="hidden md:block md:contents">
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

                <div class="mx-4 hidden sm:block">
                    <a href="/releases" target="_blank">Version: {{GameVersion::version()}}</a>
                </div>

                <div class="mx-4 hidden sm:block">
                    <a href="/information/where-to-start" target="_blank">Help, I'm Stuck!</a>
                </div>

                <div>
                    <label class="switch switch_outlined" data-toggle="tooltip" data-tippy-content="Toggle Dark Mode">
                        <input id="darkModeToggler" type="checkbox">
                        <span></span>
                        <i class="fas fa-adjust pl-2"></i>
                    </label>
                </div>

                <!-- User Menu -->
                @include('layouts.partials.profile-drop-down')
            @endauth
        </div>
    </header>

    @auth
        @if(auth()->user()->hasRole('Admin'))
            @include('layouts.partials.sidebar.adminsidebar')
        @else
            @include('layouts.partials.sidebar.playersidebar')
        @endif
    @endauth

    <!-- Workspace -->
    @auth
        @if (auth()->user()->hasRole('Admin'))
            <main class="workspace mb-10 dark:bg-gray-900">
        @else
            <main class="workspace  dark:bg-gray-900">
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

    @guest;
        </main>
    @endguest
    <!-- Scripts -->

    @livewireScriptConfig

    @vite('resources/js/vendor/theme-script.js')

    @if (!is_null(auth()->user()))
        @if (!auth()->user()->hasRole('Admin'))
            @vite('resources/js/app.ts')
        @else
            @vite('resources/js/admin-app.ts')
        @endif
    @endif

    @stack('scripts')
</body>
</html>
