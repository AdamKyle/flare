@props(['user'])

<div class="relative">
    <button
        type="button"
        id="app-profile-menu-toggle"
        aria-haspopup="menu"
        aria-expanded="false"
        aria-controls="app-profile-menu"
        aria-label="Toggle user menu for {{ $user->hasRole('Admin') ? 'Administrator' : $user->character->name }}"
        class="flex items-center text-gray-700 dark:text-gray-400"
    >
        <span class="mr-3 h-11 w-11 overflow-hidden rounded-full">
            <img
                src="{{ asset('character-images/knight-in-a-field.png') }}"
                alt="User"
                class="h-full w-full object-cover"
            />
        </span>

        <span class="text-theme-sm mr-1 block font-medium">
            {{ $user->hasRole('Admin') ? 'Administrator' : $user->character->name }}
        </span>

        <i aria-hidden="true" class="fas fa-chevron-down stroke-gray-500 transition-transform dark:stroke-gray-400"></i>
    </button>

    <div
        id="app-profile-menu"
        class="shadow-theme-lg absolute right-0 mt-[17px] hidden w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-700"
    >
        <div>
            <span class="text-theme-sm mt-4 ml-4 block font-bold text-gray-700 dark:text-gray-400">
                {{ $user->hasRole('Admin') ? 'Administrator' : $user->character->name }}
            </span>
        </div>

        <ul class="flex flex-col gap-1 border-b border-gray-200 pt-4 pb-3 dark:border-gray-800">
            @unless ($user->hasRole('Admin'))
                <li>
                    <a
                        href="{{ route('user.settings', ['user' => $user]) }}"
                        class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                    >
                        <i class="fas fa-cog" aria-hidden="true"></i>
                        <span>Settings</span>
                    </a>
                </li>
            @endunless

            <li>
                <a
                    href="#"
                    class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <i class="fas fa-question-circle" aria-hidden="true"></i>
                    <span>Help Docs</span>
                </a>
            </li>
            <li>
                <a
                    href="#"
                    class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <i class="far fa-file-alt" aria-hidden="true"></i>
                    <span>Release Notes</span>
                </a>
            </li>
        </ul>

        <button
            type="button"
            class="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
            onclick="
                event.preventDefault();
                document.getElementById('logout-form-profile').submit();
            "
        >
            <i class="fas fa-sign-out-alt group-hover:fill-gray-700 dark:fill-gray-400" aria-hidden="true"></i>
            Sign out
        </button>

        <form id="logout-form-profile" action="{{ route('logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</div>
