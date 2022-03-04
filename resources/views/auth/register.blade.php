@extends('layouts.app')

@section('content')

    <div class="container flex items-center justify-center mt-20 py-10">
        <div class="w-full md:w-1/2 xl:w-1/3">
            <div class="mx-5 md:mx-10">
                <h2 class="uppercase">Begin your journey</h2>
                <h4 class="uppercase">Let's roll that character up!</h4>
            </div>
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
                           data-tippy-content="Character names may not contain spaces an can only be 15 characters long and only contain letters and numbers (of any case)."
                        ></i>
                    </label>
                    <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
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
                <div class="flex">
                    <x-core.buttons.primary-button css="ltr:ml-auto rtl:mr-auto uppercase" type="submit">
                        Register
                    </x-core.buttons.primary-button>
                </div>
            </x-core.cards.form-card>
        </div>
    </div>
@endsection

