<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @guest
    @else
        <meta name="game_key" content="{{ auth()->user()->private_game_key }}">
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
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="fix-header fix-sidebar card-no-border mini-sidebar">
    <div id="main-wrapper">
        <div class="page-wrapper">
            <div class="container-fluid mb-2">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
