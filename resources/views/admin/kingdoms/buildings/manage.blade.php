@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="{{!is_null($building) ? 'Edit: ' . nl2br($building->name) : 'Create New Building'}}"
      buttons="true"
      backUrl="{{!is_null($building) ? route('buildings.building', ['building' => $building->id]) : route('buildings.list')}}"
    >
      <x-core.form-wizard.container
        action="{{route('buildings.store')}}"
        modelId="{{!is_null($building) ? $building->id : 0}}"
        lastTab="tab-style-2-5"
      >
        <x-core.form-wizard.tabs>
          <x-core.form-wizard.tab
            target="tab-style-2-1"
            primaryTitle="Basic Info"
            secondaryTitle="Basic building info."
            isActive="true"
          />
          <x-core.form-wizard.tab
            target="tab-style-2-2"
            primaryTitle="Upgrade Costs"
            secondaryTitle="Building Upgrade Costs"
          />
          <x-core.form-wizard.tab
            target="tab-style-2-3"
            primaryTitle="Gain Upon Upgrade"
            secondaryTitle="What does the building get when upgraded?"
          />
          <x-core.form-wizard.tab
            target="tab-style-2-4"
            primaryTitle="Unit Recruiment (Optional)"
            secondaryTitle="When the building can recruit units."
          />
        </x-core.form-wizard.tabs>

        <x-core.form-wizard.contents>
          <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
            <div class="grid gap-2 md:grid-cols-2">
              <div>
                <h3 class="mb-3">Basic Building Info</h3>
                <x-core.forms.input
                  :model="$building"
                  label="Name:"
                  modelKey="name"
                  name="name"
                  type="text"
                />
                <x-core.forms.text-area
                  :model="$building"
                  label="Description:"
                  modelKey="description"
                  name="description"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Max Level:"
                  modelKey="max_level"
                  name="max_level"
                  type="text"
                />

                <div
                  class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
                ></div>
                <h3 class="mb-3">Other Attributes</h3>
                <x-core.forms.check-box
                  :model="$building"
                  label="Are we a wall?"
                  modelKey="is_wall"
                  name="is_wall"
                />
                <x-core.forms.check-box
                  :model="$building"
                  label="Are we a Farm?"
                  modelKey="is_farm"
                  name="is_farm"
                />
                <x-core.forms.check-box
                  :model="$building"
                  label="Are we a Church?"
                  modelKey="is_church"
                  name="is_church"
                />
                <x-core.forms.check-box
                  :model="$building"
                  label="Are we a Resource Building?"
                  modelKey="is_resource_building"
                  name="is_resource_building"
                />

                <div
                  class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
                ></div>
                <h3 class="mb-3">Is Special?</h3>
                <p class="mb-3 text-sm">
                  Special buildings cannot be leveled with gold.
                </p>
                <x-core.forms.check-box
                  :model="$building"
                  label="Special?"
                  modelKey="is_special"
                  name="is_special"
                />

                <div
                  class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
                ></div>
                <h3 class="mb-3">Morale Increment/Decrement per Hour</h3>
                <x-core.forms.input
                  :model="$building"
                  label="Increasesd Morale By (%):"
                  modelKey="increase_morale_amount"
                  name="increase_morale_amount"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Decreases Morale By (%):"
                  modelKey="decrease_morale_amount"
                  name="decrease_morale_amount"
                  type="text"
                />
              </div>
              <div
                class="my-6 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
              ></div>
              <div>
                <h3 class="mb-3">Base Requirements</h3>
                <x-core.forms.input
                  :model="$building"
                  label="Base Required Pop:"
                  modelKey="required_population"
                  name="required_population"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Base Durability:"
                  modelKey="base_durability"
                  name="base_durability"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Base Defence:"
                  modelKey="base_defence"
                  name="base_defence"
                  type="text"
                />
                <x-core.forms.collection-select
                  :model="$building"
                  label="Passive Skill Required (Optional)"
                  name="passive_skill_id"
                  key="name"
                  value="id"
                  :options="$passiveSkills"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Passive Level Required?:"
                  modelKey="level_required"
                  name="level_required"
                  type="text"
                />
                <x-core.forms.check-box
                  :model="$building"
                  label="is Building Locked?"
                  modelKey="is_locked"
                  name="is_locked"
                />
              </div>
            </div>
          </x-core.form-wizard.content>
          <x-core.form-wizard.content target="tab-style-2-2">
            <div class="grid gap-4 md:grid-cols-2">
              <div>
                <h3 class="mb-3">Cost Info</h3>
                <x-core.forms.input
                  :model="$building"
                  label="Wood Cost:"
                  modelKey="wood_cost"
                  name="wood_cost"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Clay Cost:"
                  modelKey="clay_cost"
                  name="clay_cost"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Stone Cost:"
                  modelKey="stone_cost"
                  name="stone_cost"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Iron Cost:"
                  modelKey="iron_cost"
                  name="iron_cost"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Steel Cost:"
                  modelKey="steel_cost"
                  name="steel_cost"
                  type="text"
                />
              </div>
              <div
                class="my-6 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
              ></div>
              <div>
                <h3 class="mb-3">Time Information</h3>
                <x-core.forms.input
                  :model="$building"
                  label="Time to build (minutes):"
                  modelKey="time_to_build"
                  name="time_to_build"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Time increases per level (%):"
                  modelKey="time_increase_amount"
                  name="time_increase_amount"
                  type="text"
                />
              </div>
            </div>
          </x-core.form-wizard.content>

          <x-core.form-wizard.content target="tab-style-2-3">
            <div class="grid gap-4 md:grid-cols-2">
              <div>
                <h3 class="mb-3">Resource Gains</h3>
                <x-core.forms.input
                  :model="$building"
                  label="Increase To Wood:"
                  modelKey="increase_wood_amount"
                  name="increase_wood_amount"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Increase To Clay:"
                  modelKey="increase_clay_amount"
                  name="increase_clay_amount"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Increase To Stone:"
                  modelKey="increase_stone_amount"
                  name="increase_stone_amount"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Increase To Iron:"
                  modelKey="increase_iron_amount"
                  name="increase_iron_amount"
                  type="text"
                />
              </div>
              <div
                class="my-6 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
              ></div>
              <div>
                <h3 class="mb-3">Building Gains</h3>
                <x-core.forms.input
                  :model="$building"
                  label="Increase To Durability:"
                  modelKey="increase_durability_amount"
                  name="increase_durability_amount"
                  type="text"
                />
                <x-core.forms.input
                  :model="$building"
                  label="Increase To Defence:"
                  modelKey="increase_defence_amount"
                  name="increase_defence_amount"
                  type="text"
                />
              </div>
            </div>
          </x-core.form-wizard.content>

          <x-core.form-wizard.content target="tab-style-2-4">
            <x-core.forms.check-box
              :model="$building"
              label="Can Train Units?"
              modelKey="trains_units"
              name="trains_units"
            />
            <x-core.forms.collection-select-no-model
              label="Units to recruit"
              name="units_to_recruit[]"
              key="name"
              value="id"
              :options="$recruitableUnits"
              :relationIds="$unitsForBuilding"
            />
            <x-core.forms.input
              :model="$building"
              label="Units Per Level:"
              modelKey="units_per_level"
              name="units_per_level"
              type="text"
            />
            <x-core.forms.input
              :model="$building"
              label="Unit At Only Level:"
              modelKey="only_at_level"
              name="only_at_level"
              type="text"
            />
          </x-core.form-wizard.content>
        </x-core.form-wizard.contents>
      </x-core.form-wizard.container>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
