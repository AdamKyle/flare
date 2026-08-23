<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
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
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" />
    <link
        rel="stylesheet"
        href="https://use.fontawesome.com/releases/v5.7.1/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr"
        crossorigin="anonymous"
    />
    @vite('resources/js/layouts/app-layout.ts')
    @stack('head')
</head>
<body class="bg-gray-100 transition-colors duration-200 dark:bg-gray-800">
    @include('layouts.partials.plain-header', [
        'isLoggedIn' => true,
        'user' => auth()->user(),
    ])
    <main>
        @yield('content')
    </main>
    @vite('resources/js/game.ts')
    <script>
        setInterval(() => {
            fetch('/api/game-heart-beat', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            });
        }, 30000);
    </script>
    @stack('scripts')
</body>
</html>
