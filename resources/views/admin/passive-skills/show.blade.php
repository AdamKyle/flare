@extends('layouts.app')

@section('content')
  <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
    <x-core.page-title
      title="{{$skill->name}}"
      route="{{route('passive.skills.list')}}"
      color="primary" link="Back"
    >
    </x-core.page-title>
    <hr />
    <x-core.cards.card>
      <p>{!! nl2br(e($skill->description)) !!}</p>
      <dl>
        <dt>Max Level:</dt>
        <dd>{{$skill->max_level}}</dd>
        <dt>Hours Per Level <sup>*</sup>:</dt>
        <dd>{{$skill->hours_per_level}}</dd>
        <dt>Bonus Per Level:</dt>
        <dd>{{$skill->bonus_per_level * 100}}%</dd>
        <dt>Effects:</dt>
        <dd>{{$skill->passiveType()->getNamedValue()}}</dd>
        @if (!is_null($skill->parent_skill_id))
          <dt>Parent Skill:</dt>
          <dd>{{$skill->parent->name}}</dd>
          <dt>Unlocks when parent is level:</dt>
          <dd>{{$skill->unlocks_at_level}}</dd>
          <dt>Is Locked?</dt>
          <dd>{{$skill->is_locked ? 'Yes' : 'No'}}</dd>
        @endif
      </dl>
      <p class="tw-mt-5"><sup>*</sup> Caution, this is not: <code>Max Level * Hours</code> for a total amount of hours. Each level will multiply the time needed by the next level.
        Ie, level 2 at 3 hours (per level) will actually take 6 hours to complete.
      </p>
    </x-core.cards.card>
    <h2 class="tw-font-light tw-mt-5">Child Skills</h2>
    <p class="tw-mt-5">
      These skills will unlock at specific levels of this skill.
    </p>
    <hr />
    @livewire('admin.passive-skills.data-table', [
        'only'    => 'children',
        'skillId' => $skill->id
    ])
  </div>
@endsection