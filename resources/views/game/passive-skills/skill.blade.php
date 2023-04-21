@extends('layouts.app')

@section('content')
  <div class="w-full lg:w-3/4 m-auto">
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
      <p class="mt-5"><sup>*</sup> Hours till next is a calculation of your current level + 1 * the hours needed at level 0. Ie, a level 1 skill, that took 4 hours at level 0, will take 8 hours to get to level 2,
        12 hours to get to level 3, 16 to get to level 4 and 20 to get to the max level of 5. Not all skills go to level five. Building passives will only go to level 1.
      </p>
    </x-core.cards.card>
    <h2 class="font-light mt-5">Child Skills</h2>
    <p class="mt-5">
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
