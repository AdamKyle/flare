<x-core.grids.two-column>
  <x-slot name="columnOne">
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
  </x-slot>
  <x-slot name="columnTwo">
    <h6 class="tw-text-center">Reward Data</h6>
    <hr/>
    <dl>
      <dt>XP</dt>
      <dd>{{$adventureLog->rewards[$level][$monsterName]['exp']}}</dd>
      <dt>Gold <sup>*</sup></dt>
      <dd>{{number_format($adventureLog->rewards[$level][$monsterName]['gold'])}}</dd>
    </dl>
    <p class="tw-mt-3">
      <sup>*</sup> This value is the monsters gold + any gold rush you may have gotten.
    </p>
  </x-slot>
</x-core.grids.two-column>