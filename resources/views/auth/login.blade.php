@extends('layouts.app')

@section('content')
    <div class="min-h-screen flex justify-center items-start pt-16 px-4">
        <div class="w-full max-w-md space-y-8">
            <header class="text-center">
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">Welcome Back!</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 uppercase">Child! Countless adventures await us!</p>
            </header>

            @if(config('app.disabled_reg_and_login'))
                <x-core.alerts.info-alert title="ATTN!">
                    Deepest apologizes, however Planes of Tlessa is currently down for maintenance and registration/login are paused. We promise to be back soon. For updates, check
                    <a href="https://discord.gg/hcwdqJUerh" target="_blank" rel="noopener" class="text-danube-600 hover:underline">
                        Discord
                    </a>.
                </x-core.alerts.info-alert>
            @endif

            <form method="POST" action="{{ route('login') }}" class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8 space-y-6">
                @csrf

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

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Password') }}
                    </label>
                    <input
                      id="password"
                      name="password"
                      type="password"
                      required
                      autocomplete="current-password"
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

                <div class="text-center">
                    <span class="text-gray-600">Don't have an account?</span>
                    <a href="{{ route('register') }}" class="ml-1 text-blue-600 hover:underline font-medium">
                        Register
                    </a>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
                    <x-core.buttons.primary-button type="submit">
                        Login
                    </x-core.buttons.primary-button>

                    <div class="flex flex-col space-y-2 text-center sm:text-right">
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
                        <a href="{{ route('password.request') }}" class="text-sm text-danube-600 hover:underline">
                            {{ __('Forgot Your Password?') }}
                        </a>
                        <a href="{{ route('un.ban.request') }}" class="text-sm text-danube-600 hover:underline">
                            {{ __('Banned Unfairly?') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
