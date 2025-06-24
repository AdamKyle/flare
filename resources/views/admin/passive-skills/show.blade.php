@extends('layouts.app')

@section('content')
  <div class="mt-10 mb-10 w-full lg:w-3/5 m-auto">
    <x-core.page.title
      title="{{$skill->name}}"
      route="{{route('passive.skills.list')}}"
      color="success"
      link="Back"
    >
        <x-core.buttons.link-buttons.primary-button
            href="{{route('passive.skill.edit', ['passiveSkill' => $skill->id])}}"
            css="ml-2"
        >
            Edit Passive
        </x-core.buttons.link-buttons.primary-button>
    </x-core.page.title>

    @include('admin.passive-skills.partials.show', [
        'skill' => $skill
    ])
@endsection
