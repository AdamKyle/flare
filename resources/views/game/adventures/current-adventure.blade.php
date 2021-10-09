@extends('layouts.app')

@section('content')

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
        <x-core.tabs.container ulCss="tw-justify-center" useHr="true">
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
                <div class="tw-flex tw-flex-wrap tw--mx-2 tw-mb-8">
                  <div class="tw-w-full lg:tw-w-1/2 tw-px-2 tw-mb-4">
                    <div class="tw-text-center">
                      <h6>Encounter Data</h6>
                      <hr/>
                      @foreach ($messages as $message)
                        <span class="{{$message['class']}}">{{$message['message']}}</span><br />
                      @endforeach
                    </div>
                    <div class="lg:tw-hidden">
                      <hr />
                    </div>
                  </div>

                  <div class="tw-w-full lg:tw-w-1/2 tw-px-2 tw-mb-4">
                    <h6 class="tw-text-center">Reward Data</h6>
                    <hr/>
                    <dl>
                      <dt>XP</dt>
                      <dd>{{$adventureLog->rewards[$level][$monsterName]['exp']}}</dd>
                      <dt>Gold <sup>*</sup></dt>
                      <dd>{{$adventureLog->rewards[$level][$monsterName]['gold']}}</dd>
                    </dl>
                    <p class="tw-mt-3">
                      <sup>*</sup> This value is the monsters gold + any gold rush you may have gotten.
                    </p>
                  </div>
                </div>
              </x-core.tabs.tab-content>
              @php $counter += 1; @endphp
            @endforeach
          </x-slot>
        </x-core.tabs.container>
      @else
        <div class="tw-flex tw-flex-wrap tw--mx-2 tw-mb-8">
          @foreach ($messages as $monsterName => $messages)
            <div class="tw-w-full lg:tw-w-1/2 tw-px-2 tw-mb-4">
              <div class="tw-text-center">
                <h6>Encounter Data</h6>
                  @foreach ($messages as $message)
                    <span class="{{$message['class']}}">{{$message['message']}}</span><br />
                  @endforeach
              </div>
              <div class="lg:tw-hidden">
                <hr />
              </div>
            </div>

            <div class="tw-w-full lg:tw-w-1/2 tw-px-2 tw-mb-4">
              <h6 class="tw-text-center">Reward Data</h6>
              <dl>
                <dt>XP</dt>
                <dd>{{$adventureLog->rewards[$level][$monsterName]['exp']}}</dd>
                <dt>Gold <sup>*</sup></dt>
                <dd>{{$adventureLog->rewards[$level][$monsterName]['gold']}}</dd>
              </dl>
              <p class="tw-mt-3">
                <sup>*</sup> This value is the monsters gold + any gold rush you may have gotten.
              </p>
            </div>
          @endforeach
        </div>
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
  @endpush
@endsection
