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
        @if ($cosmeticText)
            @include('game.core.settings.partials.cosmetic-text', [
                'uses' => $user,
            ])
        @endif
        @if ($cosmeticNameTag)
            @include('game.core.settings.partials.cosmetic-name-tags', [
                'uses' => $user,
            ])
        @endif
        @include('game.core.settings.partials.auto-disenchant-settings', [
            'user' => $user,
        ])
        @include('game.core.settings.partials.chat-settings', [
            'user' => $user,
        ])
    </div>
@endsection
