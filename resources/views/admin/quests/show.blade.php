@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    @php
      $backUrl = route('quests.index');

      if (
        ! auth()
          ->user()
          ->hasRole('Admin')
      ) {
        $backUrl = '/information/quests';
      }
    @endphp

    <x-core.cards.card-with-title
      title="{{$quest->name}}"
      buttons="true"
      backUrl="{{$backUrl}}"
      editUrl="{{route('quests.edit', ['quest' => $quest->id])}}"
    >
      @include('admin.quests.partials.show', ['quest' => $quest])
    </x-core.cards.card-with-title>

    <div class="grid gap-2 md:grid-cols-2">
      <x-core.cards.card-with-title title="Before Completion Text">
        <p>
          {!! nl2br($quest->before_completion_description) !!}
        </p>
      </x-core.cards.card-with-title>

      <x-core.cards.card-with-title title="After Completion Text">
        <p>
          {!! nl2br($quest->after_completion_description) !!}
        </p>
      </x-core.cards.card-with-title>
    </div>
  </x-core.layout.info-container>
@endsection
