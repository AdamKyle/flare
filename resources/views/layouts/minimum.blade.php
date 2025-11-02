<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Language" content="en" />
    <meta name="google" content="notranslate" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @guest
    @else
      <meta name="player" content="{{ auth()->user()->id }}" />

      @if (! auth()->user()->hasRole('Admin'))
        <meta name="character" content="{{ auth()->user()->character->id }}" />
      @endif
    @endguest

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com" />
    <link
      href="https://fonts.googleapis.com/css?family=Nunito"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://use.fontawesome.com/releases/v5.7.1/css/all.css"
      integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr"
      crossorigin="anonymous"
    />

    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css"
    />

    @vite('resources/css/tailwind.css')

    @vite('resources/vendor/theme/assets/js/dark-mode/dark-mode.js')

    @stack('head')
  </head>

  <body>
    <!-- Top Bar -->
    <header class="top-bar">
      <!-- Brand -->
      <span class="brand"><a href="/">Planes of Tlessa</a></span>

      <!-- Right -->
      <div class="absolute right-0 mr-2 flex items-center">
        <!-- Dark Mode -->

        @guest
          <div class="hidden lg:contents">
            <label
              class="switch switch_outlined"
              data-toggle="tooltip"
              data-tippy-content="Toggle Dark Mode"
            >
              <input id="darkModeToggler" type="checkbox" />
              <span></span>
            </label>
            <span class="ml-4">Test Dark Mode</span>

            <a href="{{ route('login') }}" class="mr-2 ml-6 text-lg">Login</a>
            |
            <a href="{{ route('register') }}" class="mr-2 ml-2 text-lg">
              Register
            </a>
          </div>
        @else
          <div>
            <label
              class="switch switch_outlined"
              data-toggle="tooltip"
              data-tippy-content="Toggle Dark Mode"
            >
              <input id="darkModeToggler" type="checkbox" />
              <span></span>
              <i class="fas fa-adjust pl-2"></i>
            </label>
          </div>
        @endguest
      </div>
    </header>

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

    @vite('resources/js/vendor/theme-script.js')
  </body>
</html>
