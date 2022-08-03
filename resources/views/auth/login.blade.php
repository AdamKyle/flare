@extends('layouts.app')

@section('content')
<div class="container small-container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="row page-titles">
                <div class="col-md-6 align-self-right">
                    <h4 class="mt-2">Login</h4>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @if (config('app.disabled_reg_and_login'))
                        <x-core.alerts.info-alert title="ATTN!">
                            Deepest apologizes, however Planes of Tlessa is currently down for maintenance and unlike other deployments,
                            this one has had to disable the Registration and Login for a short time. We promise to be back up and running soon
                            and hope to see you all in-game soon. For more info, please check <a href="https://discord.gg/hcwdqJUerh" target="_blank">Discord.</a>
                        </x-core.alerts.info-alert>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="ml-2" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif

                                <a class="ml-2" href="{{ route('un.ban.request') }}">
                                    {{ __('Banned Unfairly?') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
