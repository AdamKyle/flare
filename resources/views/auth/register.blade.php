@extends('layouts.app')

@section('content')
  <div class="min-h-screen flex justify-center items-start pt-16 px-4">
    <div class="w-full max-w-md space-y-8">
      <header class="text-center">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">Begin Your Journey</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 uppercase">Let's roll that character up!</p>
      </header>

      @if(config('app.disabled_reg_and_login'))
        <x-core.alerts.info-alert title="ATTN!">
          Deepest apologizes, however Planes of Tlessa is currently down for maintenance and registration/login are
          paused. We promise to be back soon. For updates, check
          <a href="https://discord.gg/hcwdqJUerh" target="_blank" rel="noopener"
             class="text-danube-600 hover:underline">
            Discord
          </a>.
        </x-core.alerts.info-alert>
      @endif

      <form method="POST" action="{{ route('register') }}"
            class="bg-white dark:bg-gray-900 shadow-lg rounded-lg p-8 space-y-6">
        @csrf

        {{-- E-Mail Address --}}
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('E-Mail Address') }}
          </label>
          <input
            id="email"
            name="email"
            type="email"
            value="{{ old('email') }}"
            required
            autocomplete="email"
            autofocus
            aria-invalid="@error('email') true @enderror"
            aria-describedby="email-error"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:bg-gray-900 dark:text-white"
          />
          @error('email')
          <p id="email-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
            {{ $message }}
          </p>
          @enderror
        </div>

        {{-- Password --}}
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('Password') }}
          </label>
          <input
            id="password"
            name="password"
            type="password"
            required
            autocomplete="new-password"
            aria-invalid="@error('password') true @enderror"
            aria-describedby="password-error"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:bg-gray-900 dark:text-white"
          />
          @error('password')
          <p id="password-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
            {{ $message }}
          </p>
          @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
          <label for="password-confirm" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('Confirm Password') }}
          </label>
          <input
            id="password-confirm"
            name="password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:bg-gray-900 dark:text-white"
          />
        </div>

        {{-- Character Creation Header --}}
        <div>
          <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">Character Creation</h3>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Check out
            <a href="/information/races-and-classes" class="text-blue-600 hover:underline">
              Races and Classes
            </a>
            for more information.
          </p>
          <hr class="mt-4 border-gray-200 dark:border-gray-700"/>
        </div>

        {{-- Name with Alpine Tooltip --}}
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Name
            <span x-data="{ tooltip: false }" class="relative inline-block ml-2 align-middle">
            <button
              type="button"
              x-on:mouseenter="tooltip = true" x-on:mouseleave="tooltip = false"
              x-on:focus="tooltip = true" x-on:blur="tooltip = false"
              x-on:click="tooltip = !tooltip"
              aria-describedby="name-hint"
              class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none"
            >
              <i class="far fa-question-circle" aria-hidden="true"></i>
              <span class="sr-only">Name requirements</span>
            </button>

            <div
              x-cloak
              x-show="tooltip"
              x-transition
              x-on:click.away="tooltip = false"
              id="name-hint"
              role="tooltip"
              class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-64 px-3 py-2 text-sm text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-[99999]"
            >
              Character names may not contain spaces and can only be 15 characters long (5 characters min) and only contain letters and numbers.
            </div>
          </span>
          </label>
          <input
            id="name"
            name="name"
            type="text"
            value="{{ old('name') }}"
            required
            minlength="5"
            maxlength="15"
            autocomplete="name"
            autofocus
            aria-invalid="@error('name') true @enderror"
            aria-describedby="name-error name-hint"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:bg-gray-900 dark:text-white"
          />
          @error('name')
          <p id="name-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
            {{ $message }}
          </p>
          @enderror
        </div>

        {{-- Choose a Race --}}
        <div>
          <label for="race" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('Choose a Race') }}
          </label>
          <div class="relative mt-1">
            <select
              id="race"
              name="race"
              required
              aria-invalid="@error('race') true @enderror"
              aria-describedby="race-error"
              class="block w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm appearance-none placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:bg-gray-900 dark:text-white"
            >
              @foreach($races as $id => $label)
                <option value="{{ $id }}" {{ old('race') == $id ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            <i
              class="fas fa-chevron-down pointer-events-none absolute top-1/2 right-3 transform -translate-y-1/2 text-gray-400 dark:text-gray-500"
              aria-hidden="true"></i>
          </div>
          @error('race')
          <p id="race-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
            {{ $message }}
          </p>
          @enderror
        </div>

        {{-- Choose a Class --}}
        <div>
          <label for="class" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('Choose a Class') }}
          </label>
          <div class="relative mt-1">
            <select
              id="class"
              name="class"
              required
              aria-invalid="@error('class') true @enderror"
              aria-describedby="class-error"
              class="block w-full px-3 py-2 pr-10 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm appearance-none placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:bg-gray-900 dark:text-white"
            >
              @foreach($classes as $id => $label)
                <option value="{{ $id }}" {{ old('class') == $id ? 'selected' : '' }}>
                  {{ $label }}
                </option>
              @endforeach
            </select>
            <i
              class="fas fa-chevron-down pointer-events-none absolute top-1/2 right-3 transform -translate-y-1/2 text-gray-400 dark:text-gray-500"
              aria-hidden="true"></i>
          </div>
          @error('class')
          <p id="class-error" class="mt-2 text-sm text-red-600 dark:text-red-400" role="alert">
            {{ $message }}
          </p>
          @enderror
        </div>

        {{-- Submit + Account Deletion --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <button
            type="submit"
            class="w-full sm:w-auto px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            {{ __('Register') }}
          </button>

          <a
            href="/information/account-deletion"
            target="_blank"
            rel="noopener"
            class="text-sm text-danube-600 hover:underline flex items-center justify-center sm:justify-end"
          >
            Account Deletion
            <i class="fas fa-external-link-alt ml-1" aria-hidden="true"></i>
            <span class="sr-only">(opens in new tab)</span>
          </a>
        </div>
      </form>
    </div>
  </div>
@endsection
