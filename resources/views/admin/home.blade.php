@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <div class="grid grid-cols-2 gap-3">
            <x-core.cards.card-with-title title="Logged In (Today) Count">
                chart
            </x-core.cards.card-with-title>

            <x-core.cards.card-with-title title="Registered (Today) Count">
                chart
            </x-core.cards.card-with-title>
        </div>

        <div class="mt-5 mb-5">
            <div id="administrator-chat"></div>
        </div>

    </x-core.layout.info-container>
@endsection
