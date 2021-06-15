<ul class="navbar-nav my-lg-0">
    <li class="nav-item ml-4">
        <a class="nav-link" href="https://discord.gg/hcwdqJUerh" target="_blank"><i class="fab fa-discord fa-lg text-info"></i></a>
    </li>
    <li class="nav-item ml-4">
        <a class="nav-link" href="https://github.com/AdamKyle/flare" target="_blank"><i class="fab fa-github fa-lg text-info"></i></a>
    </li>
    <li class="nav-item ml-4">
        <a class="nav-link" href="/releases" target="_blank">Vs: {{GameVersion::version()}}</a>
    </li>
    <li class="nav-item ml-4">
        <a class="nav-link btn-link" href="/information/home" target="_blank">Help! I'm Stuck</a>
    </li>
    @if (!auth()->user()->hasRole('Admin'))
        <li class="nav-item ml-4" id="notification-center">
        </li>
    @endif
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle text-muted" href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fas fa-user"></i></a>
        <div class="dropdown-menu dropdown-menu-right animated flipInY">
            <ul class="dropdown-user">
                <li>
                    @if (\Request::route()->getName() === 'info.page')
                        <a href="/">
                            <i class="fas fa-home"></i> Home
                        </a>
                    @else
                        <a href="{{route('info.page', ['pageName' => 'home'])}}">
                            <i class="fas fa-question-circle"></i> Help
                        </a>

                        @if (!auth()->user()->hasRole('Admin'))
                            <a href="{{route('user.settings', ['user' => auth()->user()->id])}}">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        @endif
                    @endif


                    <a class="link" href="{{ route('logout') }}"
                        data-toggle="tooltip"
                        title="Logout"
                        onclick="event.preventDefault();
                                document.getElementById('logout-form-profile').submit();">
                        <i class="fas fa-power-off"></i> Logout
                    </a>

                    <form id="logout-form-profile" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
    </li>
</ul>
