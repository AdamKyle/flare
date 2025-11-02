@extends('layouts.app')

@section('content')
  <div class="m-auto mt-10 mb-10 w-full lg:w-3/5">
    <x-core.page.title
      title="{{is_null($skill) ? 'Create New Passive Skill' : 'Edit: ' . $skill->name}}"
      route="{{route('passive.skills.list')}}"
      color="primary"
      link="Back"
    ></x-core.page.title>

    <x-core.cards.card>
      <form
        action="{{ is_null($skill) ? route('passive.skill.store') : route('passive.skill.update', ['passiveSkill' => $skill->id]) }}"
        method="POST"
      >
        @csrf()
        <x-core.forms.input
          :model="$skill"
          label="Name:"
          modelKey="name"
          name="name"
        />
        <x-core.forms.text-area
          :model="$skill"
          label="Description:"
          modelKey="description"
          name="description"
        />
        <x-core.forms.input
          :model="$skill"
          label="Max Level:"
          modelKey="max_level"
          name="max_level"
        />
        <x-core.forms.key-value-select
          :model="$skill"
          label="Effects:"
          modelKey="effect_type"
          name="effect_type"
          :options="$effects"
        />
        <x-core.forms.input
          :model="$skill"
          label="Bonus per level (%):"
          modelKey="bonus_per_level"
          name="bonus_per_level"
        />
        <x-core.forms.input
          :model="$skill"
          label="Bonus resources per level (optional):"
          modelKey="resource_bonus_per_level"
          name="resource_bonus_per_level"
        />
        <x-core.forms.input
          :model="$skill"
          label="Capital City Building Travel Time Reduction % (Optional):"
          modelKey="capital_city_building_request_travel_time_reduction"
          name="capital_city_building_request_travel_time_reduction"
        />
        <x-core.forms.input
          :model="$skill"
          label="Capital City Building Travel Time Reduction % (Optional):"
          modelKey="capital_city_unit_request_travel_time_reduction"
          name="capital_city_unit_request_travel_time_reduction"
        />
        <x-core.forms.input
          :model="$skill"
          label="Resource Request Travel Time Reduction % (Optional):"
          modelKey="resource_request_time_reduction"
          name="resource_request_time_reduction"
        />
        <x-core.forms.key-value-select
          :model="$skill"
          label="Belongs to Skill:"
          modelKey="parent_skill_id"
          name="parent_skill_id"
          :options="$parentSkills"
        />
        <x-core.forms.input
          :model="$skill"
          label="Unlocks at level:"
          modelKey="unlocks_at_level"
          name="unlocks_at_level"
        />
        <x-core.forms.input
          :model="$skill"
          label="Hours per level:"
          modelKey="hours_per_level"
          name="hours_per_level"
        />
        <x-core.forms.check-box
          :model="$skill"
          label="Is Locked?"
          modelKey="is_locked"
          name="is_locked"
        />
        <x-core.forms.check-box
          :model="$skill"
          label="Is Parent Skill?"
          modelKey="is_parent"
          name="is_parent"
        />

        @if (is_null($skill))
          <x-core.buttons.primary-button type="submit">
            Create Passive
          </x-core.buttons.primary-button>
        @else
          <x-core.buttons.primary-button type="submit">
            Update Passive
          </x-core.buttons.primary-button>
        @endif
      </form>
    </x-core.cards.card>
  </div>
@endsection
