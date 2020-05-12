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

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/default.css')}}" id="theme" rel="stylesheet">
</head>
<body class="fix-header fix-sidebar card-no-border">
    <div id="main-wrapper">
        <div id="app">
            <header class="topbar">
                <nav class="navbar top-navbar navbar-expand-md navbar-light">
                    <!-- ============================================================== -->
                    <!-- Logo -->
                    <!-- ============================================================== -->
                    <div class="navbar-header">
                        <a class="navbar-brand" href="index.html">
                            <!-- Logo icon --><b>
                                <!-- Logo Icon-->
                            </b>
                            <!--End Logo icon -->
                            <!-- Logo text --><span> <!-- Logo Goes Here --> </span> </a>
                    </div>
                    <!-- ============================================================== -->
                    <!-- End Logo -->
                    <!-- ============================================================== -->
                    <div class="navbar-collapse">

                        @guest
                        @else
                            <!-- ============================================================== -->
                            <!-- toggle and nav items -->
                            <!-- ============================================================== -->
                            <ul class="navbar-nav mr-auto mt-md-0 ">
                                <!-- This is  -->
                                <li class="nav-item"> <a class="nav-link nav-toggler hidden-md-up text-muted waves-effect waves-dark" href="javascript:void(0)"><i class="ti-menu"></i></a> </li>
                                <!-- ============================================================== -->
                                <!-- Comment -->
                                <!-- ============================================================== -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle text-muted text-muted waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="mdi mdi-message"></i>
                                        <div class="notify"> <span class="heartbit"></span> <span class="point"></span> </div>
                                    </a>
                                    <div class="dropdown-menu mailbox animated bounceInDown">
                                        <ul>
                                            <li>
                                                <div class="drop-title">Notifications</div>
                                            </li>
                                            <li>
                                                <div class="message-center">
                                                    <!-- Message -->
                                                    <a href="#">
                                                        <div class="btn btn-danger btn-circle"><i class="fa fa-link"></i></div>
                                                        <div class="mail-contnet">
                                                            <h5>Luanch Admin</h5> <span class="mail-desc">Just see the my new admin!</span> <span class="time">9:30 AM</span> </div>
                                                    </a>
                                                    <!-- Message -->
                                                    <a href="#">
                                                        <div class="btn btn-success btn-circle"><i class="ti-calendar"></i></div>
                                                        <div class="mail-contnet">
                                                            <h5>Event today</h5> <span class="mail-desc">Just a reminder that you have event</span> <span class="time">9:10 AM</span> </div>
                                                    </a>
                                                    <!-- Message -->
                                                    <a href="#">
                                                        <div class="btn btn-info btn-circle"><i class="ti-settings"></i></div>
                                                        <div class="mail-contnet">
                                                            <h5>Settings</h5> <span class="mail-desc">You can customize this template as you want</span> <span class="time">9:08 AM</span> </div>
                                                    </a>
                                                    <!-- Message -->
                                                    <a href="#">
                                                        <div class="btn btn-primary btn-circle"><i class="ti-user"></i></div>
                                                        <div class="mail-contnet">
                                                            <h5>Pavan kumar</h5> <span class="mail-desc">Just see the my admin!</span> <span class="time">9:02 AM</span> </div>
                                                    </a>
                                                </div>
                                            </li>
                                            <li>
                                                <a class="nav-link text-center" href="javascript:void(0);"> <strong>Check all notifications</strong> <i class="fa fa-angle-right"></i> </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                <!-- ============================================================== -->
                                <!-- End Comment -->
                                <!-- ============================================================== -->
                                <!-- ============================================================== -->
                                <!-- Messages -->
                                <!-- ============================================================== -->
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="" id="2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="mdi mdi-email"></i>
                                        <div class="notify"> <span class="heartbit"></span> <span class="point"></span> </div>
                                    </a>
                                    <div class="dropdown-menu mailbox animated bounceInDown" aria-labelledby="2">
                                        <ul>
                                            <li>
                                                <div class="drop-title">You have 4 new messages</div>
                                            </li>
                                            <li>
                                                <div class="message-center">
                                                    <!-- Message -->
                                                    <a href="#">
                                                        <div class="user-img"> <img src="../assets/images/users/1.jpg" alt="user" class="img-circle"> <span class="profile-status online float-right"></span> </div>
                                                        <div class="mail-contnet">
                                                            <h5>Pavan kumar</h5> <span class="mail-desc">Just see the my admin!</span> <span class="time">9:30 AM</span> </div>
                                                    </a>
                                                    <!-- Message -->
                                                    <a href="#">
                                                        <div class="user-img"> <img src="../assets/images/users/2.jpg" alt="user" class="img-circle"> <span class="profile-status busy float-right"></span> </div>
                                                        <div class="mail-contnet">
                                                            <h5>Sonu Nigam</h5> <span class="mail-desc">I've sung a song! See you at</span> <span class="time">9:10 AM</span> </div>
                                                    </a>
                                                    <!-- Message -->
                                                    <a href="#">
                                                        <div class="user-img"> <img src="../assets/images/users/3.jpg" alt="user" class="img-circle"> <span class="profile-status away float-right"></span> </div>
                                                        <div class="mail-contnet">
                                                            <h5>Arijit Sinh</h5> <span class="mail-desc">I am a singer!</span> <span class="time">9:08 AM</span> </div>
                                                    </a>
                                                    <!-- Message -->
                                                    <a href="#">
                                                        <div class="user-img"> <img src="../assets/images/users/4.jpg" alt="user" class="img-circle"> <span class="profile-status offline float-right"></span> </div>
                                                        <div class="mail-contnet">
                                                            <h5>Pavan kumar</h5> <span class="mail-desc">Just see the my admin!</span> <span class="time">9:02 AM</span> </div>
                                                    </a>
                                                </div>
                                            </li>
                                            <li>
                                                <a class="nav-link text-center" href="javascript:void(0);"> <strong>See all e-Mails</strong> <i class="fa fa-angle-right"></i> </a>
                                            </li>
                                        </ul>
                                    </div>
                                </li>
                                <!-- ============================================================== -->
                                <!-- End Messages -->
                                <!-- ============================================================== -->

                            </ul>
                        @endguest
                        <!-- ============================================================== -->
                        <!-- User profile -->
                        <!-- ============================================================== -->
                        @guest
                        @else
                            <ul class="navbar-nav my-lg-0">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="../assets/images/users/1.jpg" alt="user" class="profile-pic" /></a>
                                    <div class="dropdown-menu dropdown-menu-right animated flipInY">
                                        <ul class="dropdown-user">
                                            <li>
                                                <div class="dw-user-box">
                                                    <div class="u-img"><img src="../assets/images/users/1.jpg" alt="user"></div>
                                                    <div class="u-text">
                                                        <h4>Steave Jobs</h4>
                                                        <p class="text-muted">varun@gmail.com</p><a href="profile.html" class="btn btn-rounded btn-danger btn-sm">View Profile</a></div>
                                                </div>
                                            </li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="#"><i class="ti-user"></i> My Profile</a></li>
                                            <li><a href="#"><i class="ti-wallet"></i> My Balance</a></li>
                                            <li><a href="#"><i class="ti-email"></i> Inbox</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="#"><i class="ti-settings"></i> Account Setting</a></li>
                                            <li role="separator" class="divider"></li>
                                            <li><a href="#"><i class="fa fa-power-off"></i> Logout</a></li>
                                        </ul>
                                    </div>
                                </li>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="flag-icon flag-icon-us"></i></a>
                                    <div class="dropdown-menu  dropdown-menu-right animated bounceInDown"> <a class="dropdown-item" href="#"><i class="flag-icon flag-icon-in"></i> India</a> <a class="dropdown-item" href="#"><i class="flag-icon flag-icon-fr"></i> French</a> <a class="dropdown-item" href="#"><i class="flag-icon flag-icon-cn"></i> China</a> <a class="dropdown-item" href="#"><i class="flag-icon flag-icon-de"></i> Dutch</a> </div>
                                </li>
                            </ul>
                        @endguest

                        <ul class="navbar-nav my-lg-0">
                            <!-- Authentication Links -->
                            @guest
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                    </li>
                                @endif
                            @else
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                        {{ Auth::user()->name }} <span class="caret"></span>
                                    </a>

                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                           onclick="event.preventDefault();
                                                         document.getElementById('logout-form').submit();">
                                            {{ __('Logout') }}
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </nav>
            </header>

            @guest
            @else
                <aside class="left-sidebar">
                    <!-- Sidebar scroll-->
                    <div class="scroll-sidebar">
                        <!-- Sidebar navigation-->
                        <nav class="sidebar-nav">
                            @if (auth()->user()->hasRole('Admin'))
                                @include('layouts.partials.sidebar.adminsidebar')
                            @else
                                @include('layouts.partials.sidebar.playersidebar')
                            @endif
                        </nav>
                        <!-- End Sidebar navigation -->
                    </div>
                    <!-- End Sidebar scroll-->
                    <!-- Bottom points-->
                    <div class="sidebar-footer">
                        <!-- item-->
                        <a href="" class="link" data-toggle="tooltip" title="Settings"><i class="ti-settings"></i></a>
                        <!-- item-->
                        <a href="" class="link" data-toggle="tooltip" title="Bug Report"><i class="mdi mdi-gmail"></i></a>
                        <!-- item-->
                        <a class="link" href="{{ route('logout') }}"
                           data-toggle="tooltip"
                           title="Logout"
                           onclick="event.preventDefault();
                                         document.getElementById('logout-form').submit();">
                            <i class="mdi mdi-power"></i>
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                    <!-- End Bottom points-->
                </aside>
            @endguest

            @guest
                <div class="container-fluid" style="min-height: 853px;">
                    @include('layouts.partials.alerts')
                    @yield('content')
                </div>

                <footer class="footer" style="left: 0;"> © 2019 Monster Admin by wrappixel.com </footer>
            @else
                <div class="page-wrapper">
                    <div class="container-fluid">
                        @include('layouts.partials.alerts')
                        @yield('content')
                    </div>
                </div>

                <footer class="footer"> © 2019 Monster Admin by wrappixel.com </footer>
            @endif
        </div>
    </div>
</body>
</html>
