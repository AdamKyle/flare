@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex justify-center items-start bg-gray-50 dark:bg-gray-900 pt-16 px-4">
        <div class="w-full max-w-md space-y-8">
            <header class="text-center">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">Reset Password</h1>
            </header>

            @if(session('status'))
                <div
                  class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6"
                  role="alert"
                >
                    {{ session('status') }}
                </div>
            @endif

            <form
              method="POST"
              action="{{ route('password.email') }}"
              class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8 space-y-6"
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
                      class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:bg-gray-900 dark:text-white"
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
                      class="w-full px-6 py-3 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        {{ __('Next Step') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
