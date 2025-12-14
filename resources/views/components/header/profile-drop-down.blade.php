@props([
  'user' => null,
])

<div
  class="relative"
  x-data="{ dropdownOpen: false }"
  @click.outside="dropdownOpen = false"
>
  @if ($user->hasRole('Admin'))
    <x-header.profile-drop-down-trigger image="{{ asset('character-images/knight-in-a-field.png') }}">
      Administrator
    </x-header.profile-drop-down-trigger>
  @else
    <x-header.profile-drop-down-trigger image="{{ asset('character-images/knight-in-a-field.png') }}">
      {{ $user->character->name }}
    </x-header.profile-drop-down-trigger>
  @endif

  <div
    x-cloak
    x-show="dropdownOpen"
    class="shadow-theme-lg absolute right-0 mt-[17px] flex w-[260px] flex-col rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-700"
  >
    @if ($user->hasRole('Admin'))
      <x-header.profile-drop-down-trigger image="{{ asset('character-images/knight-in-a-field.png') }}">
        Administrator
      </x-header.profile-drop-down-trigger>
    @else
      <x-header.profile-drop-down-name>
        {{ $user->character->name }}
      </x-header.profile-drop-down-name>
    @endif

    <x-header.profile-drop-down-options-container>
      @if (! $user->hasRole('Admin'))
        <x-header.profile-drop-down-option
          href="{{ route('user.settings', ['user' => auth()->user()]) }}"
          icon="fas fa-cog"
        >
          Settings
        </x-header.profile-drop-down-option>
      @endif

      <x-header.profile-drop-down-option href="#" icon="fas fa-question-circle">
        Help Docs
      </x-header.profile-drop-down-option>
      <x-header.profile-drop-down-option href="#" icon="far fa-file-alt">
        Release Notes
      </x-header.profile-drop-down-option>
    </x-header.profile-drop-down-options-container>

    <button
      class="group text-theme-sm mt-3 flex items-center gap-3 rounded-lg px-3 py-2 font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg:white/5 dark:hover:text-gray-300"
      onclick="event.preventDefault();
        document.getElementById('logout-form-profile').submit();"
    >
      <i
        class="fas fa-sign-out-alt group-hover:fill-gray-700 dark:fill-gray-400"
      ></i>
      Sign out
    </button>

    <form
      id="logout-form-profile"
      action="{{ route('logout') }}"
      method="POST"
      class="hidden"
    >
      @csrf
    </form>
  </div>
</div>
