<x-core.grids.two-column>
  <x-slot name="columnOne">
    <div class="text-center">
      <h6>Encounter Data</h6>
      <hr/>
      @foreach ($messages as $message)
        @if (isset($message['message']) && isset($message['message']))
          <span class="{{$message['class']}}">{{$message['message']}}</span><br />
        @endif
      @endforeach
    </div>
    <div class="lg:hidden">
      <hr />
    </div>
  </x-slot>
  <x-slot name="columnTwo">
    <h6 class="text-center">Reward Data</h6>
    <hr/>
    @if (!is_null($adventureLog->rewards))
      @include('game.adventures.partials.rewards', ['adventureLog' => $adventureLog, 'character' => $character, 'topLevelRewards' => false])

      <p class="mt-6 text-blue-500">Gold includes any gold rushes and adventure bonuses</p>
      <p class="text-blue-500">Skill XP and XP includes any item bonuses, adventure bonuses and map bonuses</p>
    @elseif ($adventureLog->took_to_long)
      <p class="text-center text-red-500">Adventure took far too long. No Rewards.</p>
    @elseif (!$adventureLog->complete)
      <p class="text-center text-red-500">You are dead and got no rewards.</p>
    @else
      <p class="text-center">You have already collected rewards for this floor.</p>
    @endif
  </x-slot>
</x-core.grids.two-column>