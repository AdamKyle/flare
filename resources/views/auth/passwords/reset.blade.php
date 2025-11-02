@extends('layouts.app')

@section('content')
  <div
    class="flex min-h-screen items-start justify-center bg-gray-50 px-4 pt-16 dark:bg-gray-900"
  >
    <div class="w-full max-w-md space-y-8">
      <header class="text-center">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
          Reset Your Password
        </h1>
        <p class="mt-2 text-sm text-gray-600 uppercase dark:text-gray-400">
          Come back and adventure with us!
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
            class="text-blue-600 hover:underline"
          >
            Discord
          </a>
          .
        </x-core.alerts.info-alert>
      @endif

      <form
        method="POST"
        action="{{ route('password.update') }}"
        class="space-y-6 rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800"
      >
        @csrf
        <input type="hidden" name="token" value="{{ $token }}" />

        @if (session('status'))
          <div
            class="mb-6 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700"
            role="alert"
          >
            {{ session('status') }}
          </div>
        @endif

        {{-- E‐Mail Address --}}
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

        {{-- New Password --}}
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
            for="password_confirmation"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            {{ __('Confirm Password') }}
          </label>
          <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            aria-invalid="@error('password_confirmation') true @enderror"
            aria-describedby="password-confirmation-error"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-blue-400"
          />
          @error('password_confirmation')
            <p
              id="password-confirmation-error"
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
            {{ __('Reset Password') }}
          </button>

          <a
            href="/information/account-deletion"
            target="_blank"
            rel="noopener"
            class="flex items-center justify-center text-sm text-blue-600 hover:underline sm:justify-end"
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
