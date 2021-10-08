@extends('layouts.app')

@section('content')
  <x-core.cards.card-with-title title="{{$adventureLog->adventure->name}}">
    @if ($adventureLog->complete)
      <p>You completed the adventure!</p>
      <div>
        <h5>Rewards</h5>
        <p>Below are your rewards for the adventure. Each card below will also show you a reward breakdown
        for each creature you killed.</p>
      </div>
    @else
      <p>You died during the adventure. Check below for more details.</p>
    @endif
  </x-core.cards.card-with-title>

  @foreach ($adventureLog->logs as $messages)
    @foreach ($messages as $level => $levelMessages)
      <x-core.cards.card-with-title title="{{'Level: ' . $level . ' Encounter'}}">
      @foreach ($levelMessages as $key => $message)
        @if (count($message) === count($message, COUNT_RECURSIVE))
          <p class={{"tw-text-center " . $message['class']}}>{{$message['message']}}</p>
        @else
          @foreach ($message as $m)
              <p class={{"tw-text-center " . $m['class']}}>{{$m['message']}}</p>
          @endforeach
        @endif
      @endforeach
      </x-core.cards.card-with-title>
    @endforeach

  @endforeach
@endsection
