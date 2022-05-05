@extends('layouts.app')

@section('content')
    <div class="container flex items-center justify-center mt-20 py-10">
        <div class="w-full md:w-1/2 xl:w-1/3">
            <div class="mx-5 md:mx-10">
                <h2 class="uppercase">Unban Request</h2>
                <h4 class="uppercase">Request Form</h4>
            </div>

            <x-core.cards.form-card css="mt-5 p-5 md:p-10" method="POST"  action="{{ route('un.ban.request.submit', ['user' => $user])}}">
                @csrf

                <div class="mb-4 mt-4">
                    <strong>Reason you were banned:</strong> {{$user->banned_reason}}
                </div>

                <div class="mb-5">
                    <label for="unban-message" class="label block mb-2">Reason</label>
                    <textarea class="form-control" id="unban-message" name="unban_message" value="{{old('unban_message')}}"></textarea>
                </div>

                <div class="flex">
                    <x-core.buttons.primary-button css="ltr:ml-auto rtl:mr-auto" type="submit">
                        Request to be unbanned
                    </x-core.buttons.primary-button>
                </div>
            </x-core.cards.form-card>


        </div>
    </div>
@endsection
