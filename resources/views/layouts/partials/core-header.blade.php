<header
  x-data="{ menuToggle: false }"
  class="sticky top-0 z-99999 flex w-full border-gray-200 bg-white lg:border-b dark:border-gray-800 dark:bg-gray-900"
>
  <div class="flex grow flex-col items-center justify-between lg:flex-row lg:px-6">
    <div
      class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-3 py-3 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4 dark:border-gray-800"
    >
      <!-- Hamburger Toggle BTN -->
      <button
        @click.stop="sidebarToggle = !sidebarToggle"
        :class="sidebarToggle ? 'lg:bg-transparent dark:lg:bg-transparent bg-gray-100 dark:bg-gray-800' : ''"
        class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg border-gray-200 text-gray-500 lg:h-11 lg:w-11 lg:border dark:border-gray-800 dark:text-gray-400"
      >
        <i class="fas fa-bars hidden lg:block"></i>
      </button>
      <!-- /Hamburger Toggle BTN -->

      <a href="index.html" class="lg:hidden">
        <img class="dark:hidden" src="./images/logo/logo.svg" alt="Logo" />
        <img class="hidden dark:block" src="./images/logo/logo-dark.svg" alt="Logo" />
      </a>

      <!-- App Nav Menu Button -->
      <button
        @click.stop="menuToggle = !menuToggle"
        :class="menuToggle ? 'bg-gray-100 dark:bg-gray-800' : ''"
        class="z-99999 flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 hover:bg-gray-100 lg:hidden dark:text-gray-400 dark:hover:bg-gray-800"
      >
        <i class="fas fa-ellipsis-h"></i>
      </button>
      <!-- /App Nav Menu Button -->
    </div>

    <div
      :class="menuToggle ? 'flex' : 'hidden'"
      class="shadow-theme-md w-full items-center justify-between gap-4 px-5 py-4 lg:flex lg:justify-end lg:px-0 lg:shadow-none"
    >
      <div class="2xsm:gap-3 flex items-center gap-2">
        <!-- Dark Mode Toggler -->
        <button
          @click.prevent="darkMode = !darkMode"
          class="hover:text-dark-900 relative flex h-11 w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
        >
          <i class="fas fa-moon hidden dark:block"></i>
          <i class="fas fa-sun dark:hidden"></i>
        </button>
        <!-- /Dark Mode Toggler -->

        <!-- Notification Menu Area (if needed) -->
      </div>

      <!-- User Area -->
      <div
        class="relative"
        x-data="{ dropdownOpen: false }"
        @click.outside="dropdownOpen = false"
      >
        <a
          href="#"
          @click.prevent="dropdownOpen = ! dropdownOpen"
          class="flex items-center text-gray-700 dark:text-gray-400"
        >
          <span class="mr-3 h-11 w-11 overflow-hidden rounded-full">
            <img src="./images/user/owner.jpg" alt="User" />
          </span>
          <span class="text-theme-sm mr-1 block font-medium">Musharof</span>
          <i
            :class="dropdownOpen && 'rotate-180'"
            class="fas fa-chevron-down stroke-gray-500 dark:stroke-gray-400"
          ></i>
        </a>

        <!-- Dropdown Start -->
        <div
          x-cloak
          x-show="dropdownOpen"
          class="shadow-theme-lg dark:bg-gray-dark absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800"
        >
          <div>
            <span class="text-theme-sm block font-medium text-gray-700 dark:text-gray-400">
              Musharof Chowdhury
            </span>
            <span class="text-theme-xs mt-0.5 block text-gray-500 dark:text-gray-400">
              randomuser@pimjo.com
            </span>
          </div>

          <ul class="flex flex-col gap-1 border-b border-gray-200 pt-4 pb-3 dark:border-gray-800">
            <li>
              <a
                href="profile.html"
                class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
              >
                <i class="fas fa-user group-hover:fill-gray-700 dark:fill-gray-400 dark:group-hover:fill-gray-300"></i>
                Edit profile
              </a>
            </li>
            <li>
              <a
                href="messages.html"
                class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
              >
                <i class="fas fa-envelope group-hover:fill-gray-700 dark:fill-gray-400 dark:group-hover:fill-gray-300"></i>
                Messages
              </a>
            </li>
            <li>
              <a
                href="settings.html"
                class="group text-theme-sm flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
              >
                <i class="fas fa-cog group-hover:fill-gray-700 dark:fill-gray-400 dark:group-hover:fill-gray-300"></i>
                Settings
              </a>
            </li>
          </ul>
          <button
            class="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
          >
            <i class="fas fa-sign-out-alt group-hover:fill-gray-700 dark:group-hover:fill-gray-300"></i>
            Sign out
          </button>
        </div>
        <!-- Dropdown End -->
      </div>
      <!-- /User Area -->
    </div>
  </div>
</header>
