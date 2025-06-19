@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-form-wizard.container
          totalSteps="2"
          name="{{ !is_null($location) ? 'Edit: ' . nl2br($location->name) : 'Create New Location' }}"
          homeRoute="{{ !is_null($location) ? route('locations.location', ['location' => $location->id]) : route('locations.list') }}"
          formAction="{{ route('locations.store') }}"
          modelId="{{ !is_null($location) ? $location->id : 0 }}"
        >
            <x-form-wizard.step stepTitle="Basic Info">
                <x-core.forms.input
                  :model="$location"
                  label="Name:"
                  modelKey="name"
                  name="name"
                />
                <x-core.forms.text-area
                  :model="$location"
                  label="Description:"
                  modelKey="description"
                  name="description"
                />
                <x-core.forms.key-value-select
                  :model="$location"
                  label="Belongs to Game Map:"
                  modelKey="game_map_id"
                  name="game_map_id"
                  :options="$gameMaps"
                />
                <x-core.forms.key-value-select
                  :model="$location"
                  label="Special Location Pin:"
                  modelKey="pin_css_class"
                  name="pin_css_class"
                  :options="$specialCssPins"
                />
                <x-core.forms.check-box
                  :model="$location"
                  label="Is Port Location?"
                  modelKey="is_port"
                  name="is_port"
                />
                <x-core.forms.check-box
                  :model="$location"
                  label="Can players enter this location?"
                  modelKey="can_players_enter"
                  name="can_players_enter"
                />
                <x-core.forms.check-box
                  :model="$location"
                  label="Can players Auto Battle at this location?"
                  modelKey="can_auto_battle"
                  name="can_auto_battle"
                />
                <x-core.forms.select
                  :model="$location"
                  label="X Position:"
                  modelKey="x"
                  name="x"
                  :options="$coordinates['x']"
                />
                <x-core.forms.select
                  :model="$location"
                  label="Y Position:"
                  modelKey="y"
                  name="y"
                  :options="$coordinates['y']"
                />
            </x-form-wizard.step>
            <x-form-wizard.step stepTitle="Details (Optional)">
                <x-core.forms.key-value-select
                  :model="$location"
                  label="Location Type:"
                  modelKey="type"
                  name="type"
                  :options="$locationTypes"
                />
                <x-core.forms.key-value-select
                  :model="$location"
                  label="Enemy Strength Increase(%):"
                  modelKey="enemy_strength_type"
                  name="enemy_strength_type"
                  :options="$locationEffects"
                />
                <x-core.forms.key-value-select
                  :model="$location"
                  label="Quest Item Required To Enter:"
                  modelKey="required_quest_item_id"
                  name="required_quest_item_id"
                  :options="$questItems"
                />

                <div
                  class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"
                ></div>
                <h3 class="mb-3">
                    Quest Item Rewards (Optional)
                </h3>
                <x-core.forms.key-value-select
                  :model="$location"
                  label="Quest Item Reward (For Visiting):"
                  modelKey="quest_reward_item_id"
                  name="quest_reward_item_id"
                  :options="$questItems"
                />
            </x-form-wizard.step>
        </x-form-wizard.container>
    </x-core.layout.info-container>
@endsection
