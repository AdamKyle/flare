@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Settings"
      buttons="true"
      backUrl="{{route('game')}}"
    >

      <h2 class="text-lg font-bold">Account Settings</h2>
      <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

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
      <div class="w-full w-2/3 space-y-2 mt-4">

        <h2 class="text-lg font-bold">General Game Settings</h2>
        <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

          @if ($user->character->level <= 10)
          <div class="border-1 border-gray-500 dark:border-gray-400 rounded-md p-4 my-4">
            @include(
              'game.core.settings.partials.enable-guide',
              [
                'user' => $user,
              ]
            )
          </div>
          @endif


        <h2 class="text-lg font-bold">Cosmetic Text</h2>
        <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

        @if ($cosmeticText)
              <div class="border-1 border-gray-500 dark:border-gray-400 rounded-md p-4 my-4">
          @include(
            'game.core.settings.partials.cosmetic-text',
            [
              'uses' => $user,
            ]
          )
              </div>
        @endif

        <h2 class="text-lg font-bold">Cosmetic Name tags</h2>
        <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

        @if ($cosmeticNameTag)
              <div class="border-1 border-gray-500 dark:border-gray-400 rounded-md p-4 my-4">
          @include(
            'game.core.settings.partials.cosmetic-name-tags',
            [
              'uses' => $user,
            ]
          )
              </div>
        @endif

        <h2 class="text-lg font-bold">Cosmetic Race Changer</h2>
        <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

        @if ($cosmeticRaceChanger)
              <div class="border-1 border-gray-500 dark:border-gray-400 rounded-md p-4 my-4">
          @include(
            'game.core.settings.partials.cosmetic-race-changer',
            [
              'uses' => $user,
            ]
          )
              </div>
        @endif

        <h2 class="text-lg font-bold">Auto Disenchant</h2>
        <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

            <div class="border-1 border-gray-500 dark:border-gray-400 rounded-md p-4 my-4">
        @include(
          'game.core.settings.partials.auto-disenchant-settings',
          [
            'user' => $user,
          ]
        )
            </div>

        <h2 class="text-lg font-bold">Chat Settings</h2>
        <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>
            <div class="border-1 border-gray-500 dark:border-gray-400 rounded-md p-4 my-4">
        @include(
          'game.core.settings.partials.chat-settings',
          [
            'user' => $user,
          ]
        )
            </div>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
