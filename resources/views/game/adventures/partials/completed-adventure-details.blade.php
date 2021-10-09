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
      @php $rewards = AdventureRewards::combineRewards($adventureLog->rewards, $character); @endphp
      <x-core.tabs.container ulCss="tw-justify-center" useHr="true" tabsId="adventure-rewards" contentId="adventure-content">
        <x-slot name="tabs">
          <x-core.tabs.tab active="true"  id="rewards-section" href="rewards">Adventure Rewards</x-core.tabs.tab>
          <x-core.tabs.tab active="false" id="items-section" href="items-gained">Items Gained</x-core.tabs.tab>
          <x-core.tabs.tab active="false" id="adventure-details-section" href="adventure-details">Adventure Reward Details</x-core.tabs.tab>
        </x-slot>
        <x-slot name="content">
          <x-core.tabs.tab-content active="true" id="rewards">
            <dl>
              <dt>Total XP:</dt>
              <dd>{{number_format($rewards['exp'])}}</dd>
              <dt>Total Gold:</dt>
              <dd>{{number_format($rewards['gold'])}}</dd>
              <dt>Skill (Currently Training):</dt>
              <dd>{{$rewards['skill']['skill_name']}}</dd>
              <dt>Skill Total XP:</dt>
              <dd>{{number_format($rewards['skill']['exp'])}}</dd>
              <dt>Skill Total XP:</dt>
              <dd>{{$rewards['skill']['exp_towards'] * 100}}%</dd>
            </dl>
            <div class="tw-mt-6 tw-mb-2">
              <p class="tw-mb-5 tw-text-blue-500">
                By clicking Collect Rewards, you will also get all associated items that your character is allowed to have.
              </p>

              <form id="collect-reward" action="{{route('game.current.adventure.reward', [
                    'adventureLog' => $adventureLog
                ])}}" method="POST" style="display: none">
                @csrf
              </form>

              <a class="tw-bg-blue-500 tw-hover:bg-blue-700 tw-text-white tw-font-bold tw-py-2 tw-px-4 tw-rounded-sm" href="#"
                 onclick="event.preventDefault();
                            document.getElementById('collect-reward').submit();"
              >
                {{ __('Collect Rewards') }}
              </a>
            </div>
          </x-core.tabs.tab-content>
          <x-core.tabs.tab-content active="false" id="items-gained">
            @if (!empty($rewards['items']))
              <ul>
                @foreach($rewards['items'] as $item)
                  <li class="tw-relative">
                    <x-item-display-color :item="$item['item']" />
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
              <p>There were no items found during this adventure.</p>
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
@php
  $logLength    = count($adventureLog->logs) - 1;
  $currentCount = 0;
@endphp
@foreach ($adventureLog->logs as $level => $messages)
  @php
    $cssClass = '';

    if ($currentCount === $logLength) {
      $cssClass = 'tw-mb-6';
    }
  @endphp
  <x-core.cards.card css="{{'tw-mt-5 tw-w-full lg:tw-w-1/2 tw-m-auto ' . $cssClass}}">
    <h3 class="tw-font-light">{{$level}}</h3>
    <p>
      Floor Description
    </p>
    <hr />
    @if (count($messages) > 1)
      <p class="lg:tw-hidden tw-mt-2 tw-mb-2 tw-text-blue-500">You can scroll to the left and right if need be.</p>
      <x-core.tabs.container ulCss="lg:tw-justify-center" useHr="true" tabsId="monster-tabs" contentId="monster-content">
        <x-slot name="tabs">
          @php $counter = 0; @endphp
          @foreach ($messages as $monsterName => $enemyMessages)
            <x-core.tabs.tab active="{{$counter === 0 ? 'true' : 'false'}}" id="{{$monsterName}}" href="{{$monsterName}}">{{explode('-', $monsterName)[0]}}</x-core.tabs.tab>
            @php $counter += 1; @endphp
          @endforeach
        </x-slot>
        <x-slot name="content">
          @php $counter = 0; @endphp

          @foreach ($messages as $monsterName => $messages)
            <x-core.tabs.tab-content active="{{$counter === 0 ? 'true' : 'false'}}" id="{{$monsterName}}" >
              @include('game.adventures.partials.floor-details', [
                  'messages'    => $messages,
                  'monsterName' => $monsterName,
                  'level'       => $level
              ])
            </x-core.tabs.tab-content>
            @php $counter += 1; @endphp
          @endforeach
        </x-slot>
      </x-core.tabs.container>
    @else
      @foreach ($messages as $monsterName => $messages)
        @include('game.adventures.partials.floor-details', [
            'messages'    => $messages,
            'monsterName' => $monsterName,
            'level'       => $level
        ])
      @endforeach
    @endif
  </x-core.cards.card>

  @php
    if ($logLength > $currentCount) {
      $currentCount += 1;
    }
  @endphp
@endforeach

@push('scripts')
  <script src="{{mix('js/page-components/tabs.js')}}" type="text/javascript"></script>
  <script>
    pageComponentTabs('#adventure-rewards', '#adventure-content')
    pageComponentTabs('#monster-tabs', '#monster-content')
  </script>
@endpush