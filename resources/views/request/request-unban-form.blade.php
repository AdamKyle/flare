@extends('layouts.app')

@section('content')
  <div class="flex min-h-screen items-start justify-center px-4 pt-16">
    <div class="w-full max-w-md space-y-8">
      <header class="text-center">
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">
          Unban Request
        </h1>
        <p class="mt-2 text-sm text-gray-600 uppercase dark:text-gray-400">
          Request Form
        </p>
      </header>

      <form
        method="POST"
        action="{{ route('un.ban.request.submit') }}"
        class="space-y-6 rounded-lg bg-white p-8 shadow-lg dark:bg-gray-800"
      >
        @csrf
        <input type="hidden" name="token" value="{{ $token }}" />

        <div>
          <label
            for="unban-message"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
          >
            Reason
          </label>
          <textarea
            id="unban-message"
            name="unban_message"
            required
            aria-invalid="{{ $errors->has('unban_message') ? 'true' : 'false' }}"
            aria-describedby="unban-message-error"
            class="mt-1 block min-h-40 w-full rounded-md border border-gray-300 px-3 py-2 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-blue-400"
          >{{ old('unban_message') }}</textarea>
          @error('unban_message')
            <p
              id="unban-message-error"
              class="mt-2 text-sm text-red-600 dark:text-red-400"
              role="alert"
            >
              {{ $message }}
            </p>
          @enderror
        </div>

        <button
          type="submit"
          class="w-full rounded-md bg-blue-600 px-6 py-3 text-sm font-medium text-white hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
        >
          Request to be unbanned
        </button>
      </form>
    </div>
  </div>
@endsection
