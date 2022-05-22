@extends('layouts.app')

@section('content')
    <div class="w-full md:w-[75%] m-auto">
        <x-core.page-title
            title="Account Settings"
            route="{{route('game')}}"
            link="Home"
        ></x-core.page-title>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        @include('game.core.settings.partials.character-name', [
            'name' => $user->character->name,
            'user' => $user,
        ])
        @include('game.core.settings.partials.account-deletion', [
            'user' => $user,
        ])
        @if ($user->character->level <= 10)
            @include('game.core.settings.partials.enable-guide', [
                'user' => $user,
            ])
        @endif
        @include('game.core.settings.partials.auto-disenchant-settings', [
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
