<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0" />
    <meta http-equiv="Content-Language" content="en" />
    <meta name="google" content="notranslate" />

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @guest
    @else
        @if (!is_null(auth()->user()->character))
            <meta name="player" content="{{ auth()->user()->character->id }}" />
        @endif
    @endguest

    <title>{{ config('app.name', 'Planes of Tlessa') }}</title>

    <x-core.pwa-meta-tags.meta-tags title="Planes of Tlessa" />

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" />
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous" />

    @auth
        @if (auth()->user()->hasRole('Admin'))
            <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
        @endif
    @endauth

    @vite('resources/css/styles.css')

    @livewireStyles

    @vite('resources/js/vendor/livewire-data-tables.js')

    @vite('resources/js/vendor/livewire.js')

    @stack('head')
</head>

<body x-data="{
    'loaded': true,
    'darkMode': false,
    'stickyMenu': false,
    'sidebarToggle': false,
    'scrollTop': false,
}" x-init="darkMode = JSON.parse(localStorage.getItem('darkMode'))
$watch('darkMode', (value) =>
    localStorage.setItem('darkMode', JSON.stringify(value)),
)" :class="{ 'dark bg-gray-800': darkMode === true }">
    <x-core.page.page-wrapper>
        @include('layouts.partials.core-side-bar')
        <x-core.page.content-area>
            @include('layouts.partials.core-header')
            <main>
                @include('layouts.partials.alerts')
                @yield('content')
            </main>
        </x-core.page.content-area>
    </x-core.page.page-wrapper>

    @livewireScriptConfig

    <script src="{{ asset('vendor/theme/script.js') }}"></script>

    @if (!is_null(auth()->user()))
        @if (!auth()->user()->hasRole('Admin'))
            @vite('resources/js/app.ts')

            <script>
                setInterval(() => {
                    fetch('/api/game-heart-beat', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                    });
                }, 30000);
            </script>
        @else
            <script>
                const lightbox = GLightbox();
            </script>
        @endif
    @endif

    @stack('scripts')
</body>

</html>
