@extends('layouts.app')

@section('content')
    <div class="container flex items-center justify-center">
        <div class="w-full md:w-1/2 xl:w-1/3">
            <div class="mx-5 md:mx-10">
                <h2 class="uppercase">Welcome back!</h2>
                <h4 class="uppercase">Child! Countless adventures await us!</h4>
            </div>

            @if (config('app.disabled_reg_and_login'))
                <x-core.alerts.info-alert title="ATTN!">
                    Deepest apologizes, however Planes of Tlessa is currently down for maintenance and unlike other deployments,
                    this one has had to disable the Registration and Login for a short time. We promise to be back up and running soon
                    and hope to see you all in-game soon. For more info, please check <a href="https://discord.gg/hcwdqJUerh" target="_blank">Discord.</a>
                </x-core.alerts.info-alert>
            @endif

            <x-core.cards.form-card css="mt-5 p-5 md:p-10" method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-5">
                    <label class="label block mb-2" for="name">{{ __('E-Mail Address') }}</label>
                    <input id="name" type="email" class="form-control" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                </div>
                <div class="mb-5">
                    <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>
                    <input id="password" type="password" class="form-control" name="password" required autocomplete="new-password" autofocus>
                    @error('password')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                </div>
                <div class="grid lg:grid-cols-1 gap-3">
                    <x-core.buttons.primary-button css="ltr:ml-auto rtl:mr-auto uppercase" type="submit">Login!</x-core.buttons.primary-button>

                    <a href="/information/account-deletion" class="ml-2" target="_blank">
                        Account Deletion <i class="fas fa-external-link-alt"></i>
                    </a>
                    <a class="ml-2" href="{{ route('password.request') }}">
                        {{ __('Forgot Your Password?') }}
                    </a>
                    <a class="ml-2" href="{{ route('un.ban.request') }}">
                        {{ __('Banned Unfairly?') }}
                    </a>
                </div>
            </x-core.cards.form-card>
        </div>
    </div>
@endsection
