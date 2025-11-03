@extends('layouts.app')

@section('content')

  <x-core.layout.info-container>
    <x-form-wizard.container
      totalSteps="10"
      name="{{ !is_null($monster) ? 'Edit: ' . nl2br($monster->name) : 'Create New Monster' }}"
      homeRoute="{{ !is_null($monster) ? route('monsters.monster', ['monster' => $monster->id]) : route('monsters.list') }}"
      formAction="{{ route('monster.store') }}"
      modelId="{{ !is_null($monster) ? $monster->id : 0 }}"
    >

      <x-form-wizard.step step-title="Basic Info">
        <x-form-elements.input
          :model="$monster"
          label="Name:"
          modelKey="name"
          name="name"
          type="text"
        />
        <x-form-elements.input
          :model="$monster"
          label="Max Level:"
          modelKey="max_level"
          name="max_level"
        />
        <x-form-elements.collection-select
          :model="$monster"
          label="Lives on map:"
          modelKey="game_map_id"
          name="game_map_id"
          value="id"
          key="name"
          :options="$gameMaps"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Basic Stats</h2>
        <x-core.separator.separator />

        <x-form-elements.select
          :model="$monster"
          label="Damage Stat:"
          modelKey="damage_stat"
          name="damage_stat"
          :options="['str', 'dex', 'agi', 'dur', 'int', 'chr', 'focus']"
        />
        <x-form-elements.input
          :model="$monster"
          label="Str:"
          modelKey="str"
          name="str"
        />
        <x-form-elements.input
          :model="$monster"
          label="Dex:"
          modelKey="dex"
          name="dex"
        />
        <x-form-elements.input
          :model="$monster"
          label="Dur:"
          modelKey="dur"
          name="dur"
        />
        <x-form-elements.input
          :model="$monster"
          label="Agi:"
          modelKey="agi"
          name="agi"
        />
        <x-form-elements.input
          :model="$monster"
          label="Int:"
          modelKey="int"
          name="int"
        />
        <x-form-elements.input
          :model="$monster"
          label="Chr:"
          modelKey="chr"
          name="chr"
        />
        <x-form-elements.input
          :model="$monster"
          label="Focus:"
          modelKey="focus"
          name="focus"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Skills">
        <x-form-elements.input
          :model="$monster"
          label="Accuracy (%):"
          modelKey="accuracy"
          name="accuracy"
        />
        <x-form-elements.input
          :model="$monster"
          label="Casting Accuracy (%):"
          modelKey="casting_accuracy"
          name="casting_accuracy"
        />
        <x-form-elements.input
          :model="$monster"
          label="Dodge (%):"
          modelKey="dodge"
          name="dodge"
        />
        <x-form-elements.input
          :model="$monster"
          label="Criticality (%):"
          modelKey="criticality"
          name="criticality"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Attack, Health & Armour">
        <x-form-elements.input
          :model="$monster"
          label="Health Range:"
          modelKey="health_range"
          name="health_range"
        />
        <x-form-elements.input
          :model="$monster"
          label="Attack Range:"
          modelKey="attack_range"
          name="attack_range"
        />
        <x-form-elements.input
          :model="$monster"
          label="Armour Class:"
          modelKey="ac"
          name="ac"
        />
        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Spell Damage (Optional)</h2>
        <x-core.separator.separator />
        <x-form-elements.check-box
          :model="$monster"
          label="Can Cast?"
          modelKey="can_cast"
          name="can_cast"
        />
        <x-form-elements.input
          :model="$monster"
          label="Max Cast Amount:"
          modelKey="max_spell_damage"
          name="max_spell_damage"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Resistances">
        <x-form-elements.input
          :model="$monster"
          label="Spell Evasion (%):"
          modelKey="spell_evasion"
          name="spell_evasion"
        />
        <x-form-elements.input
          :model="$monster"
          label="Affix Damage Resistance (%):"
          modelKey="affix_resistance"
          name="affix_resistance"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Ambush & Counter">
        <x-form-elements.input
          :model="$monster"
          label="Ambush Chance (%):"
          modelKey="ambush_chance"
          name="ambush_chance"
        />
        <x-form-elements.input
          :model="$monster"
          label="Ambush Resistance (%):"
          modelKey="ambush_resistance"
          name="ambush_resistance"
        />
        <x-form-elements.input
          :model="$monster"
          label="Counter Chance (%):"
          modelKey="counter_chance"
          name="counter_chance"
        />
        <x-form-elements.input
          :model="$monster"
          label="Counter Resistance (%):"
          modelKey="counter_resistance"
          name="counter_resistance"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Elemental Atonement">
        <p class="mb-3">
          This allows creatures to have elemental atonements, the highest
          value is used for attack. This should only be used for raid
          creatures, where characters have to make use of gems. This can
          also be used for other server side fight creatures.
        </p>
        <x-form-elements.input
          :model="$monster"
          label="Fire Atonement (%):"
          modelKey="fire_atonement"
          name="fire_atonement"
        />
        <x-form-elements.input
          :model="$monster"
          label="Ice Atonement (%):"
          modelKey="ice_atonement"
          name="ice_atonement"
        />
        <x-form-elements.input
          :model="$monster"
          label="Water Atonement (%):"
          modelKey="water_atonement"
          name="water_atonement"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Misc Modifiers">
        <x-form-elements.input
          :model="$monster"
          label="Max Affix Damage:"
          modelKey="max_affix_damage"
          name="max_affix_damage"
        />
        <x-form-elements.input
          :model="$monster"
          label="Healing (%):"
          modelKey="healing_percentage"
          name="healing_percentage"
        />
        <x-form-elements.input
          :model="$monster"
          label="Entrancing Chance (%):"
          modelKey="entrancing_chance"
          name="entrancing_chance"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Celestial Details (Optional)">
        <x-form-elements.check-box
          :model="$monster"
          label="Is Celestial Entity?"
          modelKey="is_celestial_entity"
          name="is_celestial_entity"
        />
        <x-form-elements.input
          :model="$monster"
          label="Gold Cost Per Summon:"
          modelKey="gold_cost"
          name="gold_cost"
        />
        <x-form-elements.input
          :model="$monster"
          label="Gold Dust Cost per Summon:"
          modelKey="gold_dust_cost"
          name="gold_dust_cost"
        />
        <x-form-elements.input
          :model="$monster"
          label="Shards Reward Per kill:"
          modelKey="shards"
          name="shards"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Raid Details (Optional)">
        <p class="mb-3 text-sm text-gray-800 dark:text-gray-300">
          Raid Bosses cannot also be raid monsters.
        </p>
        <x-form-elements.check-box
          :model="$monster"
          label="Is Raid Boss?"
          modelKey="is_raid_boss"
          name="is_raid_boss"
        />
        <x-form-elements.check-box
          :model="$monster"
          label="(or) Is Raid Monster?"
          modelKey="is_raid_monster"
          name="is_raid_monster"
        />
        <x-form-elements.key-value-select
          :model="$monster"
          label="Special Raid Attack Type:"
          modelKey="raid_special_attack_type"
          name="raid_special_attack_type"
          :options="$specialAttacks"
        />
        <x-form-elements.input
          :model="$monster"
          label="Life Stealing Resistance:"
          modelKey="life_stealing_resistance"
          name="life_stealing_resistance"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Belongs to specific location? (Optional)</h2>
        <p class="text-center">
          This monster can only be fought at a specific location which
          shares the same selected type as below.
        </p>
        <x-core.separator.separator />

        <x-form-elements.key-value-select
          :model="$monster"
          label="Only For Location Type:"
          modelKey="only_for_location_type"
          name="only_for_location_type"
          :options="$locationTypes"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Rewards">
        <x-form-elements.input
          :model="$monster"
          label="XP Per Kill:"
          modelKey="xp"
          name="xp"
        />
        <x-form-elements.input
          :model="$monster"
          label="Gold Per Kill:"
          modelKey="gold"
          name="gold"
        />
        <x-form-elements.input
          :model="$monster"
          label="Drop Check (%):"
          modelKey="drop_check"
          name="drop_check"
        />
        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Quest item Reward? (Optional)</h2>
        <x-core.separator.separator />
        <x-form-elements.collection-select
          :model="$monster"
          label="Quest Item to Drop:"
          modelKey="quest_item_id"
          name="quest_item_id"
          value="id"
          key="affix_name"
          :options="$questItems"
        />
        <x-form-elements.input
          :model="$monster"
          label="Quest item Drop Chance:"
          modelKey="quest_item_drop_chance"
          name="quest_item_drop_chance"
        />
      </x-form-wizard.step>

    </x-form-wizard.container>

  </x-core.layout.info-container>
@endsection
