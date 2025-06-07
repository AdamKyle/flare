@extends('layouts.app')

@section('content')
    <div class="container flex items-center justify-center mt-20 py-10">
        <div class="w-full md:w-1/2 xl:w-1/3">
            <div class="mx-5 md:mx-10">
                <h2 class="uppercase">Unban Request</h2>
                <h4 class="uppercase">Email Verification Form</h4>
            </div>
            <x-core.cards.form-card css="mt-5 p-5 md:p-10" method="POST" action="{{ route('un.ban.request.email') }}">
                @csrf

                <div class="mb-5">
                    <label class="label block mb-2" for="name">
                        {{ __('E-Mail Address') }}
                    </label>
                    <input id="name" type="email" class="form-control" name="email" value="{{ old('email') }}"
                        required autocomplete="email" autofocus />
                    @error('email')
                        <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                            <strong>{{ $message }}</strong>
                        </div>
                    @enderror
                </div>

                <div class="flex">
                    <x-core.buttons.primary-button css="ltr:ml-auto rtl:mr-auto" type="submit">
                        Next Step
                    </x-core.buttons.primary-button>
                </div>
            </x-core.cards.form-card>
        </div>
    </div>
@endsection
