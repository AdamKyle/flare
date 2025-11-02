@extends('layouts.app')

@section('content')
  <div
    class="flex min-h-screen items-start justify-center bg-gray-50 px-4 pt-16 dark:bg-gray-900"
  >
    <div class="w-full max-w-md space-y-8">
      <header class="text-center">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
          Reset Password
        </h1>
      </header>

      @if (session('status'))
        <div
          class="mb-6 rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700"
          role="alert"
        >
          {{ session('status') }}
        </div>
      @endif

      <form
        method="POST"
        action="{{ route('password.email') }}"
        class="space-y-6 rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800"
      >
        @csrf

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

        <div>
          <button
            type="submit"
            class="w-full rounded-md bg-blue-600 px-6 py-3 text-sm font-medium text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
          >
            {{ __('Next Step') }}
          </button>
        </div>
      </form>
    </div>
  </div>
@endsection
