@extends('layouts.app')

@section('content')
  <div class="flex min-h-screen items-start justify-center px-4 pt-16">
    <div class="w-full max-w-md space-y-8">
      <header class="text-center">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
          Begin Your Journey
        </h1>
        <p class="mt-2 text-sm text-gray-600 uppercase dark:text-gray-400">
          Let's roll that character up!
        </p>
      </header>

      @if (config('app.disabled_reg_and_login'))
        <x-core.alerts.info-alert title="ATTN!">
          Deepest apologizes, however Planes of Tlessa is currently down for
          maintenance and registration/login are paused. We promise to be back
          soon. For updates, check
          <a
            href="https://discord.gg/hcwdqJUerh"
            target="_blank"
            rel="noopener"
            class="text-danube-600 hover:underline"
          >
            Discord
          </a>
          .
        </x-core.alerts.info-alert>
      @endif

      <form
        method="POST"
        action="{{ route('register') }}"
        class="space-y-6 rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800"
      >
        @csrf

        {{-- E-Mail Address --}}
        <div>
          <label
            for="email"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
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
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-blue-400"
          />
          @error('email')
            <p
              id="email-error"
              class="mt-2 text-sm text-red-600 dark:text-red-400"
              role="alert"
            >
              {{ $message }}
            </p>
          @enderror
        </div>

        {{-- Password --}}
        <div>
          <label
            for="password"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
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
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-blue-400"
          />
          @error('password')
            <p
              id="password-error"
              class="mt-2 text-sm text-red-600 dark:text-red-400"
              role="alert"
            >
              {{ $message }}
            </p>
          @enderror
        </div>

        {{-- Confirm Password --}}
        <div>
          <label
            for="password-confirm"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ __('Confirm Password') }}
          </label>
          <input
            id="password-confirm"
            name="password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-blue-400"
          />
        </div>

        {{-- Character Creation Header --}}
        <div>
          <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300">
            Character Creation
          </h3>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Check out
            <a
              href="/information/races-and-classes"
              class="text-blue-600 hover:underline"
            >
              Races and Classes
            </a>
            for more information.
          </p>
          <hr class="mt-4 border-gray-200 dark:border-gray-700" />
        </div>

        {{-- Name with Alpine Tooltip --}}
        <div>
          <label
            for="name"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Name
            <span
              x-data="{ tooltip: false }"
              class="relative ml-2 inline-block align-middle"
            >
              <button
                type="button"
                x-on:mouseenter="tooltip = true"
                x-on:mouseleave="tooltip = false"
                x-on:focus="tooltip = true"
                x-on:blur="tooltip = false"
                x-on:click="tooltip = !tooltip"
                aria-describedby="name-hint"
                class="text-gray-400 hover:text-gray-600 focus:outline-none dark:text-gray-500 dark:hover:text-gray-300"
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
                class="absolute bottom-full left-1/2 z-[99999] mb-2 w-64 -translate-x-1/2 transform rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 shadow-lg dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
              >
                Character names may not contain spaces and can only be 15
                characters long (5 characters min) and only contain letters and
                numbers.
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
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-blue-400"
          />
          @error('name')
            <p
              id="name-error"
              class="mt-2 text-sm text-red-600 dark:text-red-400"
              role="alert"
            >
              {{ $message }}
            </p>
          @enderror
        </div>

        {{-- Choose a Race --}}
        <div>
          <label
            for="race"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ __('Choose a Race') }}
          </label>
          <div class="relative mt-1">
            <select
              id="race"
              name="race"
              required
              aria-invalid="@error('race') true @enderror"
              aria-describedby="race-error"
              class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 pr-10 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-blue-400"
            >
              @foreach ($races as $id => $label)
                <option
                  value="{{ $id }}"
                  {{ old('race') == $id ? 'selected' : '' }}
                >
                  {{ $label }}
                </option>
              @endforeach
            </select>
            <i
              class="fas fa-chevron-down pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 dark:text-gray-500"
              aria-hidden="true"
            ></i>
          </div>
          @error('race')
            <p
              id="race-error"
              class="mt-2 text-sm text-red-600 dark:text-red-400"
              role="alert"
            >
              {{ $message }}
            </p>
          @enderror
        </div>

        {{-- Choose a Class --}}
        <div>
          <label
            for="class"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ __('Choose a Class') }}
          </label>
          <div class="relative mt-1">
            <select
              id="class"
              name="class"
              required
              aria-invalid="@error('class') true @enderror"
              aria-describedby="class-error"
              class="block w-full appearance-none rounded-md border border-gray-300 px-3 py-2 pr-10 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-blue-400"
            >
              @foreach ($classes as $id => $label)
                <option
                  value="{{ $id }}"
                  {{ old('class') == $id ? 'selected' : '' }}
                >
                  {{ $label }}
                </option>
              @endforeach
            </select>
            <i
              class="fas fa-chevron-down pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 transform text-gray-400 dark:text-gray-500"
              aria-hidden="true"
            ></i>
          </div>
          @error('class')
            <p
              id="class-error"
              class="mt-2 text-sm text-red-600 dark:text-red-400"
              role="alert"
            >
              {{ $message }}
            </p>
          @enderror
        </div>

        {{-- Submit + Account Deletion --}}
        <div
          class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0"
        >
          <button
            type="submit"
            class="w-full rounded-md bg-blue-600 px-6 py-3 text-sm font-medium text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none sm:w-auto"
          >
            {{ __('Register') }}
          </button>

          <a
            href="/information/account-deletion"
            target="_blank"
            rel="noopener"
            class="text-danube-600 flex items-center justify-center text-sm hover:underline sm:justify-end"
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
