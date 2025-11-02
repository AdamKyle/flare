@extends('layouts.app')

@section('content')
  <div class="m-auto mt-10 mb-10 w-full lg:w-3/5">
    <x-core.page.title
      title="{{$npc->real_name}}"
      route="{{route('npcs.index')}}"
      color="success"
      link="Back"
    >
      <x-core.buttons.link-buttons.primary-button
        href="{{route('npcs.edit', ['npc' => $npc->id])}}"
        css="ml-2"
      >
        Edit NPC
      </x-core.buttons.link-buttons.primary-button>
    </x-core.page.title>

    @include('admin.npcs.partials.show', ['npc' => $npc])
  </div>
@endsection
