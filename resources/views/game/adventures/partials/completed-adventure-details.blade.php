@include('game.adventures.partials.completed-adventure-rewards', ['adventureLog' => $adventureLog, 'character' => $character])

<x-core.cards.card-with-title title="Adventure Description" css="mt-5 w-full lg:w-1/2 m-auto">
  <p>{{$adventureLog->adventure->description}}</p>
</x-core.cards.card-with-title>

@include('game.adventures.partials.completed-adventure-levels', ['adventureLog' => $adventureLog, 'character' => $character])