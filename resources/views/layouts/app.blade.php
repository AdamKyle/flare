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

    <script src={{mix('js/kingdom-unit-movement.js')}} type="text/javascript"></script>
    <script src={{mix('js/character-boons.js')}} type="text/javascript"></script>
    <script src={{mix('js/character-inventory.js')}} type="text/javascript"></script>
    <script src={{mix('js/character-sheet.js')}} type="text/javascript"></script>

    @stack('head')
</head>
@php
    $previousUrlIsInfo = strpos(url()->previous(), 'information') !== false;
@endphp

<body>
    <header class="top-bar">

        <!-- Menu Toggler -->
        <button type="button" class="menu-toggler la la-bars" data-toggle="menu"></button>

        <!-- Brand -->
        <span class="brand">Planes of Tlessa</span>


        <!-- Right -->
        <div class="flex items-center absolute right-0 mr-2">

            <!-- Dark Mode -->
            <label class="switch switch_outlined" data-toggle="tooltip" data-tippy-content="Toggle Dark Mode">
                <input id="darkModeToggler" type="checkbox">
                <span></span>
            </label>

            <!-- Notifications -->
            <div class="dropdown self-stretch mr-3 ml-3">
                <button type="button"
                        class="relative flex items-center h-full btn-link ltr:ml-1 rtl:mr-1 px-2 text-2xl leading-none la la-bell"
                        data-toggle="custom-dropdown-menu" data-tippy-arrow="true" data-tippy-placement="bottom-end">
                            <span
                              class="absolute top-0 right-0 rounded-full border border-primary -mt-1 -mr-1 px-2 leading-tight text-xs font-body text-primary">3</span>
                </button>
                <div class="custom-dropdown-menu">
                    <div class="flex items-center px-5 py-2">
                        <h5 class="mb-0 uppercase">Notifications</h5>
                        <button class="btn btn_outlined btn_warning uppercase ltr:ml-auto rtl:mr-auto">Clear All</button>
                    </div>
                    <hr>
                    <div class="p-5 hover:bg-primary-100 dark:hover:bg-primary-900">
                        <a href="#">
                            <h6 class="uppercase">Heading One</h6>
                        </a>
                        <p>Lorem ipsum dolor, sit amet consectetur.</p>
                        <small>Today</small>
                    </div>
                    <hr>
                    <div class="p-5 hover:bg-primary-100 dark:hover:bg-primary-900">
                        <a href="#">
                            <h6 class="uppercase">Heading Two</h6>
                        </a>
                        <p>Mollitia sequi dolor architecto aut deserunt.</p>
                        <small>Yesterday</small>
                    </div>
                    <hr>
                    <div class="p-5 hover:bg-primary-100 dark:hover:bg-primary-900">
                        <a href="#">
                            <h6 class="uppercase">Heading Three</h6>
                        </a>
                        <p>Nobis reprehenderit sed quos deserunt</p>
                        <small>Last Week</small>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="dropdown">
                <button class="flex items-center ltr:ml-4 rtl:mr-4 text-gray-700" data-toggle="custom-dropdown-menu"
                        data-tippy-arrow="true" data-tippy-placement="bottom-end">
                    <span class="avatar">JD</span>
                </button>
                <div class="custom-dropdown-menu w-64">
                    <div class="p-5">
                        <h5 class="uppercase">John Doe</h5>
                        <p>Editor</p>
                    </div>
                    <hr>
                    <div class="p-5">
                        <a href="#"
                           class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary">
                            <span class="la la-user-circle text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                            View Profile
                        </a>
                        <a href="#"
                           class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary mt-5">
                            <span class="la la-key text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                            Change Password
                        </a>
                    </div>
                    <hr>
                    <div class="p-5">
                        <a href="#"
                           class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary">
                            <span class="la la-power-off text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
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
                    @if(!auth()->user()->hasRole('Admin'))
                        <div id="refresh"></div>
                    @endif

                    @include('layouts.partials.alerts')
                    @yield('content')
                </div>
            </div>
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
