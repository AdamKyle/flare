<x-core.grids.two-column>
  <x-slot name="columnOne">
    <div class="tw-text-center">
      <h6>Encounter Data</h6>
      <hr/>
      @foreach ($messages as $message)
        @if (isset($message['message']) && isset($message['message']))
          <span class="{{$message['class']}}">{{$message['message']}}</span><br />
        @endif
      @endforeach
    </div>
    <div class="lg:tw-hidden">
      <hr />
    </div>
  </x-slot>
  <x-slot name="columnTwo">
    <h6 class="tw-text-center">Reward Data</h6>
    <hr/>
    @if (!is_null($adventureLog->rewards))
      @include('game.adventures.partials.rewards', ['adventureLog' => $adventureLog, 'character' => $character, 'topLevelRewards' => false])

      <p class="tw-mt-6 tw-text-blue-500">Gold includes any gold rushes and adventure bonuses</p>
      <p class="tw-text-blue-500">Skill XP and XP includes any item bonuses, adventure bonuses and map bonuses</p>
    @elseif (!$adventureLog->complete)
      <p class="tw-text-center tw-text-red-500">You are dead and got no rewards.</p>
    @else
      <p class="tw-text-center">You have already collected rewards for this floor.</p>
    @endif
  </x-slot>
</x-core.grids.two-column>