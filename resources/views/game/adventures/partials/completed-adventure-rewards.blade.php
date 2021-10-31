<x-core.cards.card-with-title title="{{$adventureLog->adventure->name}}" css="tw-mt-5 tw-w-full lg:tw-w-1/2 tw-m-auto">
  @if ($adventureLog->complete)
    <p class="tw-text-green-600">You completed the adventure!</p>
    <div>
      <h5>Rewards</h5>
      <p>Below are your rewards for the adventure. Each card below will also show you a reward breakdown
        for each creature you killed.</p>
      <p>The rewards shown below are a complete collection of all the floors. If you would like to see what you got per floor,
        check below.</p>
      <hr />

      <x-core.tabs.container ulCss="tw-justify-center" useHr="true" tabsId="adventure-rewards" contentId="adventure-content">
        <x-slot name="tabs">
          <x-core.tabs.tab active="true"  id="rewards-section" href="rewards">Adventure Rewards</x-core.tabs.tab>
          <x-core.tabs.tab active="false" id="items-section" href="items-gained">Items Gained</x-core.tabs.tab>
          <x-core.tabs.tab active="false" id="adventure-details-section" href="adventure-details">Adventure Reward Details</x-core.tabs.tab>
        </x-slot>
        <x-slot name="content">
          <x-core.tabs.tab-content active="true" id="rewards">
            @if (!is_null($adventureLog->rewards))
              @include('game.adventures.partials.rewards', ['adventureLog' => $adventureLog, 'character' => $character, 'topLevelRewards' => true])

              <div class="tw-mt-6 tw-mb-2">
                <x-core.alerts.info-alert>
                  <p>
                    By clicking Collect Rewards, you will also get all associated items that your character is allowed to have.
                  </p>
                  <p>
                    If you cannot collect anymore items, you can come back after you have made some room in your inventory and collect the remaining items.
                  </p>
                  <p>
                    If the XP/Skill XP go above 100, you will get whats called: Spill over. This means youll get the total levels of the XP / 100 with the remaining amount
                    that cannot be divided by 100 on top of the existing xp you have. Ie: 250 XP Reward while you have 60 XP, gets you 3 total levels.
                  </p>
                </x-core.alerts.info-alert>

                <form id="collect-reward" action="{{route('game.current.adventure.reward', [
                      'adventureLog' => $adventureLog
                  ])}}" method="POST" style="display: none">
                  @csrf
                </form>

                <x-core.buttons.link-buttons.success-button
                  attributes="onclick='event.preventDefault();
                  document.getElementById('collect-reward').submit();'"
                >
                  Collect Rewards
                </x-core.buttons.link-buttons.success-button>
              </div>
            @else
              <p class="tw-text-center">You have already collected the rewards for this adventure</p>
            @endif
          </x-core.tabs.tab-content>
          @php
            $rewards = is_null($adventureLog->rewards) ? [] : $adventureLog->rewards;
            $rewards = AdventureRewards::combineRewards($rewards, $character);
          @endphp
          <x-core.tabs.tab-content active="false" id="items-gained">
            @if (!empty($rewards['items']))
              <ul>
                @foreach($rewards['items'] as $item)
                  <li class="tw-relative">
                    <a class="hover:tw-underline hover:tw-text-decoration-color" target="_blank" href="{{route('game.items.item', ['item' => $item['id']])}}"><x-item-display-color :item="$item['item']" /></a>
                    @if (!$item['can_have'])
                      <div class="tw-group tw-inline-block">
                        <i class="fas fa-exclamation-circle tw-ml-2 tw-text-red-600 tw-cursor-pointer"></i>
                        <x-core.tooltips.tooltip>
                          You already have this item or have had the item and upgrade it. You cannot obtain this item again.
                        </x-core.tooltips.tooltip>
                      </div>
                    @endif
                  </li>
                @endforeach
              </ul>
            @else
              @if (is_null($adventureLog->rewards))
                <p class="tw-text-center">You already collected the items for this adventure.</p>
              @else
                <p class="tw-text-center">There were no items found during this adventure.</p>
              @endif
            @endif
          </x-core.tabs.tab-content>
          <x-core.tabs.tab-content active="false" id="adventure-details">
            <dl>
              <dt>Levels</dt>
              <dd>{{$adventureLog->adventure->levels}}</dd>
              <dt>Time Per Level (Minutes)</dt>
              <dd>{{$adventureLog->adventure->time_per_level}}</dd>
              <dt>Item Find Chance</dt>
              <dd>{{$adventureLog->adventure->item_find_chance * 100}}%</dd>
              <dt>Gold Rush Chance</dt>
              <dd>{{$adventureLog->adventure->gold_rush_chance * 100}}%</dd>
              <dt>Skill Bonus EXP</dt>
              <dd>{{$adventureLog->adventure->skill_exp_bonus * 100}}%</dd>
              <dt>EXP Bonus</dt>
              <dd>{{$adventureLog->adventure->exp_bonus * 100}}%</dd>
            </dl>
            <p class="tw-mt-3">To see more details about this adventure, <a href="{{route('map.adventures.adventure', ['adventure' => $adventureLog->adventure->id])}}">follow me</a></p>
          </x-core.tabs.tab-content>
        </x-slot>
      </x-core.tabs.container>

    </div>
  @else
    <p class="tw-text-red-600">You died during the adventure. Check below for more details.</p>
  @endif
</x-core.cards.card-with-title>