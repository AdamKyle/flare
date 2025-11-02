@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="{{!is_null($raid) ? 'Edit: ' . nl2br($raid->name) : 'Create New Race'}}"
      buttons="true"
      backUrl="{{!is_null($raid) ? route('admin.raids.show', ['raid' => $raid->id]) : route('admin.raids.list')}}"
    >
      <x-core.form-wizard.container
        action="{{route('admin.raids.store')}}"
        modelId="{{!is_null($raid) ? $raid->id : 0}}"
        lastTab="tab-style-2-5"
      >
        <x-core.form-wizard.tabs>
          <x-core.form-wizard.tab
            target="tab-style-2-1"
            primaryTitle="Basic Info"
            secondaryTitle="Basic information about the race."
            isActive="true"
          />
        </x-core.form-wizard.tabs>
        <x-core.form-wizard.contents>
          <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
            <h3 class="mb-3">Basic Info</h3>
            <x-core.forms.input
              :model="$raid"
              label="Name:"
              modelKey="name"
              name="name"
            />
            <x-core.forms.text-area
              :model="$raid"
              label="Story:"
              modelKey="story"
              name="story"
            />
            <x-core.forms.text-area
              :model="$raid"
              label="Scheduled Event Description:"
              modelKey="scheduled_event_description"
              name="scheduled_event_description"
            />
            <x-core.forms.collection-select
              :model="$raid"
              label="Raid Boss:"
              modelKey="raid_boss_id"
              name="raid_boss_id"
              value="id"
              key="name"
              :options="$raidBosses"
            />
            <x-core.forms.collection-select
              :model="$raid"
              label="Raid Boss Location:"
              modelKey="raid_boss_location_id"
              name="raid_boss_location_id"
              value="id"
              key="name"
              :options="$locations"
            />
            <x-core.forms.key-value-select
              :model="$raid"
              label="Raid Type:"
              modelKey="raid_type"
              name="raid_type"
              :options="$raidTypes"
            />
            <x-core.forms.collection-select-no-model
              label="Raid Monsters"
              name="raid_monster_ids[]"
              key="name"
              value="id"
              :options="$monsters"
              :relationIds="is_null($raid) ? [] : $raid->raid_monster_ids"
            />
            <x-core.forms.collection-select-no-model
              label="Raid Corrupted Locations (Optional)"
              name="corrupted_location_ids[]"
              key="name"
              value="id"
              :options="$locations"
              :relationIds="is_null($raid) ? [] : $raid->corrupted_location_ids"
            />
            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Specialty Item Reward Type</h3>
            <p class="mb-3 text-sm italic">
              Items of this "specialty type" will drop at random for players
              when the raid completes. It is ideal to have multiple items,
              usually weapons and armour of this type to drop.
            </p>
            <x-core.forms.select
              :model="$raid"
              label="Specialty Reward Type:"
              modelKey="item_specialty_reward_type"
              name="item_specialty_reward_type"
              :options="$itemTypes"
            />
            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Ancestral Item Reward</h3>
            <p class="mb-3 text-sm italic">
              This item will be rewarded to the one that kills the Raid Boss
            </p>
            <x-core.forms.collection-select
              :model="$raid"
              label="Ancestrral Item:"
              modelKey="artifact_item_id"
              name="artifact_item_id"
              value="id"
              key="name"
              :options="$artifacts"
            />
          </x-core.form-wizard.content>
        </x-core.form-wizard.contents>
      </x-core.form-wizard.container>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
