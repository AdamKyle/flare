<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1, viewport-fit=cover"
    />
    <meta http-equiv="Content-Language" content="en" />
    <meta name="google" content="notranslate" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    @guest
    @else
      @if (! is_null(auth()->user()->character))
        <meta name="player" content="{{ auth()->user()->character->id }}" />
      @endif
    @endguest
    <title>{{ config('app.name', 'Planes of Tlessa') }}</title>
    <x-core.pwa-meta-tags.meta-tags title="Planes of Tlessa" />
    <script>
      (function () {
        try {
          const dm = JSON.parse(localStorage.getItem('darkMode'));
          document.documentElement.classList.toggle('dark', dm);
        } catch {}
      })();
    </script>
    @vite('resources/css/styles.css')
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
    @auth
      @if (auth()->user()->hasRole('Admin'))
        <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
        <link
          rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css"
        />
      @endif
    @endauth

    @livewireStyles
    @vite('resources/js/vendor/livewire-data-tables.js')
    @vite('resources/js/vendor/livewire.js')
    @stack('head')
  </head>
  <body
    x-data="{
      loaded: true,
      darkMode: false,
      stickyMenu: false,
      sidebarToggle: false,
      scrollTop: false,
    }"
    x-init="
      darkMode = JSON.parse(localStorage.getItem('darkMode'))
      document.documentElement.classList.toggle('dark', darkMode)
      $watch('darkMode', (value) => {
        localStorage.setItem('darkMode', JSON.stringify(value))
        document.documentElement.classList.toggle('dark', value)
      })
    "
    class="bg-gray-100 transition-colors duration-200 dark:bg-gray-800"
  >
    <x-core.page.page-wrapper>
      @include('layouts.partials.core-side-bar')
      <x-core.page.content-area>
        <x-header.core-header
          :user="auth()->user()"
          :isLoggedIn="!is_null(auth()->user())"
        />
        <main>
          @include('layouts.partials.alerts')
          @yield('content')
        </main>
      </x-core.page.content-area>
    </x-core.page.page-wrapper>
    @livewireScriptConfig
    @if (! is_null(auth()->user()))
      @if (! auth()->user()->hasRole('Admin'))
        @vite('resources/js/app.ts')
        <script>
          setInterval(() => {
            fetch('/api/game-heart-beat', {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
          }, 30000);
        </script>
      @else
        @vite('resources/js/admin-apps.ts')

        <script>
          const lightbox = GLightbox();
        </script>
      @endif
    @endif

    @stack('scripts')
  </body>
</html>
