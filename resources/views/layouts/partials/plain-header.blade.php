<header class="sticky top-0 z-99999 flex w-full border-gray-200 bg-white lg:border-b dark:border-gray-800 dark:bg-gray-900">
    <div class="flex grow flex-col items-center justify-between lg:flex-row lg:px-6">
        <div class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4 dark:border-gray-800">
            <a href="/" class="text-2xl text-gray-900 dark:text-gray-300">
                <h1>Planes of Tlessa</h1>
            </a>

            <button
                type="button"
                id="app-header-menu-toggle"
                aria-expanded="false"
                aria-controls="app-header-menu"
                aria-label="Toggle menu"
                class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 lg:hidden dark:text-gray-400 dark:hover:bg-gray-800"
            >
                <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
            </button>
        </div>

        <div
            id="app-header-menu"
            class="shadow-theme-md hidden w-full items-center justify-between gap-4 px-5 py-4 lg:flex lg:justify-end lg:px-0 lg:shadow-none"
        >
            <div class="2xsm:gap-3 flex items-center gap-2">
                <button
                    type="button"
                    id="app-header-dark-mode-toggle"
                    aria-label="Toggle dark mode"
                    class="relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    <i class="fas fa-moon hidden dark:block" aria-hidden="true"></i>
                    <i class="fas fa-sun dark:hidden" aria-hidden="true"></i>
                    <span class="sr-only">Toggle dark mode</span>
                </button>
            </div>

            <div>
                <a
                    href="{{ route('releases.list') }}"
                    aria-label="Version 2.0.0 release notes"
                    class="text-danube-500 dark:text-danube-300 focus:ring-danube px-4 hover:underline focus:ring-2 focus:ring-offset-2 focus:outline-none"
                >
                    Vs. 2.0.0
                </a>
            </div>

            @if ($isLoggedIn)
                @include('layouts.partials.plain-profile-menu', ['user' => $user])
            @else
                <div>
                    <x-core.buttons.link-buttons.login-button href="{{ route('login') }}">
                        Login
                    </x-core.buttons.link-buttons.login-button>
                </div>
            @endif
        </div>
    </div>
</header>
