@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Chat Logs"
            route="{{route('home')}}"
            color="success" link="Home"
        >
        </x-core.page-title>

        <x-core.cards.card>
            Place logs table here ....
        </x-core.cards.card>
    </x-core.layout.info-container>
@endsection
