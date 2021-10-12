@include('game.adventures.partials.completed-adventure-rewards', ['adventureLog' => $adventureLog, 'character' => $character])

<x-core.cards.card-with-title title="Adventure Description" css="tw-mt-5 tw-w-full lg:tw-w-1/2 tw-m-auto">
  <p>{{$adventureLog->adventure->description}}</p>
</x-core.cards.card-with-title>

@include('game.adventures.partials.completed-adventure-levels', ['adventureLog' => $adventureLog, 'character' => $character])