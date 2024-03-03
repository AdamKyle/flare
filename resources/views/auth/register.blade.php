@extends('layouts.app')

@section('content')

    <div class="container flex items-center justify-center">
        <div class="w-full md:w-1/2 xl:w-1/3">
            <div class="mx-5 md:mx-10">
                <h2 class="uppercase">Begin your journey</h2>
                <h4 class="uppercase">Let's roll that character up!</h4>
            </div>

            @if (config('app.disabled_reg_and_login'))
                <x-core.alerts.info-alert title="ATTN!">
                    Deepest apologizes, however Planes of Tlessa is currently down for maintenance and unlike other deployments,
                    this one has had to disable the Registration and Login for a short time. We promise to be back up and running soon
                    and hope to see you all in-game soon. For more info, please check <a href="https://discord.gg/hcwdqJUerh" target="_blank">Discord.</a>
                </x-core.alerts.info-alert>
            @endif

            <x-core.cards.form-card css="mt-5 p-5 md:p-10" method="POST" action="{{ route('register') }}">
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
                <div class="mb-5">
                    <label for="password-confirm" class="col-md-4 col-form-label text-md-right">{{ __('Confirm Password') }}</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" autofocus>
                </div>
                <div class="mb-5">
                    <h3>Character Creation</h3>
                    <p class="py-3">
                        Check out <a href="/information/races-and-classes">Races and Classes</a> for more information.
                    </p>
                    <hr />
                </div>
                <div class="mb-5">
                    <label class="label block mb-2" for="name">
                        Name
                        <i class="far fa-question-circle pl-2 cursor"
                           data-toggle="tooltip"
                           data-tippy-placement="right"
                           data-tippy-content="Character names may not contain spaces an can only be 15 characters long (5 characters min) and only contain letters and numbers (of any case)."
                        ></i>
                    </label>
                    <input id="name"
                           type="text"
                           class="form-control"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           autocomplete="name"
                           autofocus
                           minlength="5"
                           maxlength="15"
                    >
                    @error('name')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                </div>
                <div class="mb-5">
                    <label for="races" class="label block mb-2">{{ __('Choose a Race') }}</label>
                    <select class="form-control" id="races" name="race">
                        @foreach($races as $id => $name)
                            <option value={{$id}} {{(int) old('race') === (int) $id ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                    @error('race')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                </div>
                <div class="mb-5">
                    <label for="classes" class="label block mb-2">{{ __('Choose a class') }}</label>

                    <select class="form-control" id="classes" name="class">
                        @foreach($classes as $id => $name)
                            <option value="{{$id}}" {{(int) old('class') === (int) $id ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                    @error('class')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                </div>
                <div class="mt-5">
                    <label for="enable_guide">
                        <input type="checkbox" name="guide_enabled" id="enable_guide">
                        <span></span>
                        <span>Enable Guide? <a href="#no-link" data-toggle="modal" data-target="#guide-explanation">(Help)</a></span>
                    </label>
                </div>
                <hr class="my-4" />
                <div class="flex my-4">
                    <a href="/information/account-deletion" css="rtl:ml-auto rtl:mr-auto" target="_blank">
                        Account Deletion <i class="fas fa-external-link-alt"></i>
                    </a>

                    <x-core.buttons.primary-button css="ltr:ml-auto rtl:mr-auto uppercase" type="submit">
                        Register
                    </x-core.buttons.primary-button>
                </div>
            </x-core.cards.form-card>
        </div>
    </div>

    @include('auth.partials.guide-quest-help')
@endsection

