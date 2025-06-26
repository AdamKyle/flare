@props([
  'isLoggedIn' => false,
  'user' => null,
])

<header
  x-data="{ menuToggle: false }"
  class="sticky top-0 z-99999 flex w-full border-gray-200 bg-white lg:border-b dark:border-gray-800 dark:bg-gray-900"
>
  <div class="flex grow flex-col items-center justify-between lg:flex-row lg:px-6">
    <div
      class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4 dark:border-gray-800"
    >
      @if ($isLoggedIn)
        <button
          @click.stop="sidebarToggle = !sidebarToggle"
          :class="sidebarToggle ? 'lg:bg-transparent dark:lg:bg-transparent bg-gray-100 dark:bg-gray-800' : ''"
          class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg border-gray-200 text-gray-500 lg:h-11 lg:w-11 lg:border dark:border-gray-800 dark:text-gray-400"
        >
          <i class="fas fa-bars hidden lg:block"></i>
        </button>
      @endif

      <a href="/" class="text-2xl text-gray-900 dark:text-gray-300">
        <h1>Planes of Tlessa</h1>
      </a>

      <button
        @click.stop="menuToggle = !menuToggle"
        :class="menuToggle ? 'bg-gray-100 dark:bg-gray-800' : ''"
        class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 lg:hidden dark:text-gray-400 dark:hover:bg-gray-800"
      >
        <i class="fas fa-ellipsis-h"></i>
      </button>
    </div>

    <div
      :class="menuToggle ? 'flex' : 'hidden'"
      class="shadow-theme-md w-full items-center justify-between gap-4 px-5 py-4 lg:flex lg:justify-end lg:px-0 lg:shadow-none"
    >
      <div class="2xsm:gap-3 flex items-center gap-2">
        <button
          @click.prevent="(() => {
            let dm = !JSON.parse(localStorage.getItem('darkMode'));
            localStorage.setItem('darkMode', JSON.stringify(dm));
            document.documentElement.classList.toggle('dark', dm);
          })()"
          class="relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        >
          <i class="fas fa-moon hidden dark:block" aria-hidden="true"></i>
          <i class="fas fa-sun dark:hidden" aria-hidden="true"></i>
          <span class="sr-only">Toggle dark mode</span>
        </button>
      </div>

      <div>
        <a
          href="{{route('releases.list')}}"
          aria-label="Version 2.0.0 release notes"
          class="text-danube-500 dark:text-danube-300 hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danube px-4"
        >
          Vs. 2.0.0
        </a>
      </div>

      @if ($isLoggedIn)
        <x-header.profile-drop-down :user="$user"/>
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
