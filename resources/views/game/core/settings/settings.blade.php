@extends('layouts.app')
@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Settings"
      buttons="true"
      backUrl="{{route('game')}}"
    >
      <h2 class="text-lg font-bold">Account Settings</h2>
      <x-core.separator.separator />
      @include(
        'game.core.settings.partials.character-name',
        [
          'name' => $user->character->name,
          'user' => $user,
        ]
      )
      @include(
        'game.core.settings.partials.account-deletion',
        [
          'user' => $user,
        ]
      )
      <div class="mt-4 w-2/3 w-full space-y-2">
        @if ($user->character->level <= 10)
          <h2 class="text-lg font-bold">Guide Settings</h2>
          <x-core.separator.separator />
          <x-core.cards.card-border>
            @include(
              'game.core.settings.partials.enable-guide',
              [
                'user' => $user,
              ]
            )
          </x-core.cards.card-border>
        @endif

        @if ($cosmeticText)
          <h2 class="text-lg font-bold">Cosmetic Text</h2>
          <x-core.separator.separator />
            <x-core.cards.card-border>
            @include(
              'game.core.settings.partials.cosmetic-text',
              [
                'uses' => $user,
              ]
            )
            </x-core.cards.card-border>
        @endif

        @if ($cosmeticNameTag)
          <h2 class="text-lg font-bold">Cosmetic Name tags</h2>
          <x-core.separator.separator />
            <x-core.cards.card-border>
            @include(
              'game.core.settings.partials.cosmetic-name-tags',
              [
                'uses' => $user,
              ]
            )
            </x-core.cards.card-border>
        @endif

        @if ($cosmeticRaceChanger)
          <h2 class="text-lg font-bold">Cosmetic Race Changer</h2>
          <x-core.separator.separator />
          <x-core.cards.card-border>
            @include(
              'game.core.settings.partials.cosmetic-race-changer',
              [
                'uses' => $user,
              ]
            )
          </x-core.cards.card-border>
        @endif

        <h2 class="text-lg font-bold">Auto Disenchant</h2>
        <x-core.separator.separator />
        <x-core.cards.card-border>
          @include(
            'game.core.settings.partials.auto-disenchant-settings',
            [
              'user' => $user,
            ]
          )
        </x-core.cards.card-border>
        <h2 class="text-lg font-bold">Chat Settings</h2>
        <x-core.separator.separator />
        <x-core.cards.card-border>
          @include(
            'game.core.settings.partials.chat-settings',
            [
              'user' => $user,
            ]
          )
        </x-core.cards.card-border>
      </div>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
