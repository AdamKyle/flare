@php
  $logLength    = count($adventureLog->logs) - 1;
  $currentCount = 0;
  $descriptions = $adventureLog->adventure->floorDescriptions->pluck('description')->toArray();
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
      {!! nl2br($descriptions[$currentCount]) !!}
    </p>
    <hr />
    @if (count($messages) > 1)
      <p class="lg:tw-hidden tw-mt-2 tw-mb-2 tw-text-blue-500">You can scroll to the left and right if need be.</p>
      <x-core.tabs.container ulCss="lg:tw-justify-center" useHr="true" tabsId="monster-tabs-{{Str::snake($level)}}" contentId="monster-content-{{Str::snake($level)}}">
        <x-slot name="tabs">
          @php $counter = 0; @endphp
          @foreach ($messages as $monsterName => $enemyMessages)
            @php $isError = AdventureRewards::messagesHasPlayerDeath($enemyMessages); @endphp
            <x-core.tabs.tab active="{{$counter === 0 ? 'true' : 'false'}}"
                             id="{{$monsterName}}"
                             href="{{$monsterName}}"
                             error="{{$isError}}"
            >
              {{explode('-', $monsterName)[0]}}
              @if ($isError)
                <i class="ra ra-bone-bite tw-text-red-600"></i>
              @endif
            </x-core.tabs.tab>
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
  </script>

  @foreach ($adventureLog->logs as $level => $messages)
    <script>
      pageComponentTabs('#monster-tabs-{{Str::snake($level)}}', '#monster-content-{{Str::snake($level)}}')
    </script>
  @endforeach
@endpush