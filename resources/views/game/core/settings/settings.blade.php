@extends('layouts.app')

@section('content')
    <div class="container">
        <x-core.page-title
            title="Account Settings"
            route="{{route('game')}}"
            link="Home"
        ></x-core.page-title>
        <hr />
        @include('game.core.settings.partials.character-name', [
            'name' => $user->character->name,
            'user' => $user,
        ])
        @include('game.core.settings.partials.account-deletion', [
            'user' => $user,
        ])
        @include('game.core.settings.partials.email-settings', [
            'user' => $user,
        ])
        @include('game.core.settings.partials.chat-settings', [
            'user' => $user,
        ])
    </div>
@endsection
