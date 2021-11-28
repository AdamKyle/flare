@extends('layouts.app')

@section('content')
  <div class="tw-w-full lg:tw-w-3/4 tw-m-auto">
    <x-core.page-title
      title="{{$skill->passiveSkill->name}}"
      route="{{url()->previous()}}"
      link="Back"
      color="primary"
    ></x-core.page-title>

    <x-core.cards.card>
      <p>{!! nl2br(e($skill->passiveSkill->description)) !!}</p>
      <dl>
        <dt>Current Level:</dt>
        <dd>{{$skill->current_level}}</dd>
        <dt>Max Level:</dt>
        <dd>{{$skill->passiveSkill->max_level}}</dd>
        <dt>Hours till next level <sup>*</sup>:</dt>
        <dd>{{$skill->hours_to_next}}</dd>
        <dt>Current Bonus:</dt>
        <dd>{{$skill->current_bonus * 100}}%</dd>
        <dt>Effects:</dt>
        <dd>{{$skill->passiveSkill->passiveType()->getNamedValue()}}</dd>
        @if (!is_null($skill->passiveSkill->parent_skill_id))
          <dt>Parent Skill:</dt>
          <dd>
            <a href="{{route('view.character.passive.skill', [
                'passiveSkill' => $skill->passiveSkill->parent->id,
                'character'    => $character->id
            ])}}">{{$skill->passiveSkill->parent->name}}</a>
          </dd>
          <dt>Unlocks when parent is level:</dt>
          <dd>{{$skill->passiveSkill->unlocks_at_level}}</dd>
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
        'only'        => 'children',
        'skillId'     => $skill->passiveSkill->id,
        'characterId' => $character->id
    ])
  </div>
@endsection
