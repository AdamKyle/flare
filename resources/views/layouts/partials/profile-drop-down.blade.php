<div class="dropdown">
    <button class="flex items-center ltr:ml-4 rtl:mr-4 text-gray-700" data-toggle="custom-dropdown-menu"
            data-tippy-arrow="true" data-tippy-placement="bottom-end">
        <span class="avatar"><i class="ra ra-player"></i> </span>
    </button>
    <div class="custom-dropdown-menu w-64">
        <div class="p-5">
            <h5 class="uppercase">
                @if (auth()->user()->hasRole('Admin'))
                    Administrator
                @else
                    {{auth()->user()->character->name}}
                @endif
            </h5>
            <p>
                @if (auth()->user()->hasRole('Admin'))
                    The Creator
                @else
                    Hero of Tlessa
                @endif
            </p>
        </div>
        <hr>
        <div class="p-5">
            <a href="{{route('user.settings', ['user' => auth()->user()->id])}}"
               class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary">
                <span class="la la-user-circle text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                Settings
            </a>
            <a href="/information/home"
               class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary mt-5" target="_blank">
                <span class="fas fa-info-circle text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                Help I am stuck!
            </a>
            <a href="https://discord.gg/hcwdqJUerh" target="_blank"
               class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary mt-5" target="_blank">
                <span class="fab fa-discord text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                Discord
            </a>
        </div>
        <hr>
        <div class="p-5">
            <a href="{{route('logout')}}"
               onclick="event.preventDefault();
                                        document.getElementById('logout-form-profile').submit();"
               class="flex items-center text-gray-700 dark:text-gray-500 hover:text-primary dark:hover:text-primary">
                <span class="la la-power-off text-2xl leading-none ltr:mr-2 rtl:ml-2"></span>
                Logout
            </a>
        </div>

        <form id="logout-form-profile" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</div>
