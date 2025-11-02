@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-form-wizard.container
      totalSteps="6"
      name="{{ !is_null($item) ? 'Edit: ' . nl2br($item->name) : 'Create New Item' }}"
      homeRoute="{{ !is_null($item) ? route('items.item', ['item' => $item->id]) : route('items.list') }}"
      formAction="{{ route('item.store') }}"
      modelId="{{ !is_null($item) ? $item->id : 0 }}"
    >
      <x-form-wizard.step stepTitle="Basic Info">

            <x-form-elements.input
              :model="$item"
              label="Name:"
              modelKey="name"
              name="name"
            />
            <x-form-elements.select
              :model="$item"
              label="Type:"
              modelKey="type"
              name="type"
              :options="$types"
            />
            <x-form-elements.text-area
              :model="$item"
              label="Description:"
              modelKey="description"
              name="description"
            />
            <x-form-elements.select
              :model="$item"
              label="Default Position (Armour only):"
              modelKey="default_position"
              name="default_position"
              :options="$defaultPositions"
            />
          <x-core.separator.separator />
            <h2 class="mb-4 text-lg text-center">Item Cost Info</h2>
            <x-form-elements.check-box
              :model="$item"
              label="Can list on market?"
              modelKey="market_sellable"
              name="market_sellable"
            />
            <x-form-elements.input
              :model="$item"
              label="Gold Cost:"
              modelKey="cost"
              name="cost"
            />
            <x-form-elements.input
              :model="$item"
              label="Shards Cost:"
              modelKey="shards_cost"
              name="shards_cost"
            />
            <x-form-elements.input
              :model="$item"
              label="Gold Dust Cost:"
              modelKey="gold_dust_cost"
              name="gold_dust_cost"
            />
            <x-form-elements.input
              :model="$item"
              label="Copper Coin Cost:"
              modelKey="copper_coin_cost"
              name="copper_coin_cost"
            />
            <x-form-elements.input
              :model="$item"
              label="Gold Bars Cost:"
              modelKey="gold_bars_cost"
              name="gold_bars_cost"
            />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="State Info">
        <x-form-elements.input
          :model="$item"
          label="Str Modifier (%):"
          modelKey="str_mod"
          name="str_mod"
        />
        <x-form-elements.input
          :model="$item"
          label="Dex Modifier (%):"
          modelKey="dex_mod"
          name="dex_mod"
        />
        <x-form-elements.input
          :model="$item"
          label="Dur Modifier (%):"
          modelKey="dur_mod"
          name="dur_mod"
        />
        <x-form-elements.input
          :model="$item"
          label="Agi Modifier (%):"
          modelKey="agi_mod"
          name="agi_mod"
        />
        <x-form-elements.input
          :model="$item"
          label="Int Modifier (%):"
          modelKey="int_mod"
          name="int_mod"
        />
        <x-form-elements.input
          :model="$item"
          label="Chr Modifier (%):"
          modelKey="chr_mod"
          name="chr_mod"
        />
        <x-form-elements.input
          :model="$item"
          label="Focus Modifier (%):"
          modelKey="focus_mod"
          name="focus_mod"
        />
        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Item Cost Info</h2>
        <x-form-elements.input
          :model="$item"
          label="Base Attack:"
          modelKey="base_damage"
          name="base_damage"
        />
        <x-form-elements.input
          :model="$item"
          label="Base AC:"
          modelKey="base_ac"
          name="base_ac"
        />
        <x-form-elements.input
          :model="$item"
          label="Base healing:"
          modelKey="base_healing"
          name="base_healing"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Modifier">

        <x-form-elements.input
          :model="$item"
          label="Base Attack Mod (%):"
          modelKey="base_damage_mod"
          name="base_damage_mod"
        />
        <x-form-elements.input
          :model="$item"
          label="Base AC Mod (%):"
          modelKey="base_ac_mod"
          name="base_ac_mod"
        />
        <x-form-elements.input
          :model="$item"
          label="Base healing Mod (%):"
          modelKey="base_healing_mod"
          name="base_healing_mod"
        />
        <x-core.separator.separator />

        <h2 class="mb-4 text-lg text-center">Resurrection Chance</h2>
        <x-form-elements.check-box
          :model="$item"
          label="Can Ressurect?"
          modelKey="can_resurrect"
          name="can_resurrect"
        />
        <x-form-elements.input
          :model="$item"
          label="Ressuectrion Chance (%):"
          modelKey="resurrection_chance"
          name="resurrection_chance"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Xp Modifiers</h2>
        <x-form-elements.input
          :model="$item"
          label="XP Bonus (%):"
          modelKey="xp_bonus"
          name="xp_bonus"
        />
        <x-form-elements.check-box
          :model="$item"
          label="Can Ignore Caps?"
          modelKey="ignores_caps"
          name="ignores_caps"
        />

        <x-core.separator.separator />

        <h2 class="mb-4 text-lg text-center">Enemy Reductions</h2>
        <x-form-elements.input
          :model="$item"
          label="Spell Evasion (%):"
          modelKey="spell_evasion"
          name="spell_evasion"
        />
        <x-form-elements.input
          :model="$item"
          label="Artifact Annulment (%):"
          modelKey="artifact_annulment"
          name="artifact_annulment"
        />
        <x-form-elements.input
          :model="$item"
          label="Affix Damage Reduction (%):"
          modelKey="affix_damage_reduction"
          name="affix_damage_reduction"
        />
        <x-form-elements.input
          :model="$item"
          label="Healing Reduction (%):"
          modelKey="healing_reduction"
          name="healing_reduction"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Devouring Light/Darkness</h2>
        <x-form-elements.input
          :model="$item"
          label="Devouring Light Chance (%):"
          modelKey="devouring_light"
          name="devouring_light"
        />
        <x-form-elements.input
          :model="$item"
          label="Devouring Darkness Chance (%):"
          modelKey="devouring_darkness"
          name="devouring_darkness"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Crafting Info">
        <x-form-elements.check-box
          :model="$item"
          label="Can Craft?"
          modelKey="can_craft"
          name="can_craft"
        />
        <x-form-elements.check-box
          :model="$item"
          label="Can Only Craft?"
          modelKey="craft_only"
          name="craft_only"
        />
        <x-form-elements.select
          :model="$item"
          label="Crafting Type:"
          modelKey="crafting_type"
          name="crafting_type"
          :options="$craftingTypes"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill Level Required:"
          modelKey="skill_level_required"
          name="skill_level_required"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill Level Trivial:"
          modelKey="skill_level_trivial"
          name="skill_level_trivial"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Holy Level</h2>
        <x-form-elements.input
          :model="$item"
          label="Holy Level:"
          modelKey="holy_level"
          name="holy_level"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Special Effects">
        <x-form-elements.select
          :model="$item"
          label="Effects (Quest items only):"
          modelKey="effect"
          name="effect"
          :options="$effects"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Class to unlock</h2>
        <x-form-elements.key-value-select
          :model="$item"
          label="Unlocks Class (Quest items only)"
          modelKey="unlocks_class_id"
          name="unlocks_class_id"
          :options="$classes"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Specialty Type</h2>
        <x-form-elements.select
          :model="$item"
          label="Specialty Type:"
          modelKey="specialty_type"
          name="specialty_type"
          :options="$specialtyTypes"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Skill Tree (Parent Skill)</h2>
        <x-form-elements.collection-select
          :model="$item"
          label="Skill Tree:"
          modelKey="item_skill_id"
          name="item_skill_id"
          value="id"
          key="name"
          :options="$itemSkills"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Drop Location</h2>
        <x-form-elements.collection-select
          :model="$item"
          label="Drops From:"
          modelKey="drop_location_id"
          name="drop_location_id"
          value="id"
          key="name"
          :options="$locations"
        />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Effects Skill</h2>
        <x-form-elements.select
          :model="$item"
          label="Skill:"
          modelKey="skill_name"
          name="skill_name"
          :options="$skills"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill Bonus:"
          modelKey="skill_bonus"
          name="skill_bonus"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill Training Bonus:"
          modelKey="skill_training_bonus"
          name="skill_training_bonus"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Alchemy Info (Usable)">

        <x-form-elements.check-box
          :model="$item"
          label="Usable?"
          modelKey="usable"
          name="usable"
        />
        <x-form-elements.check-box
          :model="$item"
          label="Can use on items?"
          modelKey="can_use_on_other_items"
          name="can_use_on_other_items"
        />
        <x-form-elements.check-box
          :model="$item"
          label="Can stack (use on self)?"
          modelKey="can_stack"
          name="can_stack"
        />
        <x-form-elements.check-box
          :model="$item"
          label="Gains a level when leveling?"
          modelKey="gains_additional_level"
          name="gains_additional_level"
        />
        <x-form-elements.input
          :model="$item"
          label="Lasts For (Minutes):"
          modelKey="lasts_for"
          name="lasts_for"
        />
        <x-form-elements.check-box
          :model="$item"
          label="Increases Stats?"
          modelKey="stat_increase"
          name="stat_increase"
        />
        <x-form-elements.input
          :model="$item"
          label="Increases All Stats By (%):"
          modelKey="increase_stat_by"
          name="increase_stat_by"
        />
        <x-form-elements.check-box
          :model="$item"
          label="Damages Kingdoms?"
          modelKey="damages_kingdoms"
          name="damages_kingdoms"
        />
        <x-form-elements.input
          :model="$item"
          label="Kingdom Damage:"
          modelKey="kingdom_damage"
          name="kingdom_damage"
        />

        <x-core.separator.separator />

        <h2 class="mb-4 text-lg text-center">Skill Info</h2>
        <x-form-elements.key-value-select
          :model="$item"
          label="Type:"
          modelKey="affects_skill_type"
          name="affects_skill_type"
          :options="$skillTypes"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill Damage Modifier (%):"
          modelKey="base_damage_mod_bonus"
          name="base_damage_mod_bonus"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill AC Modifier (%):"
          modelKey="base_ac_mod_bonus"
          name="base_ac_mod_bonus"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill Healing Modifier (%):"
          modelKey="base_healing_mod_bonus"
          name="base_healing_mod_bonus"
        />
        <x-form-elements.input
          :model="$item"
          label="Fight Timeout Modifier (%):"
          modelKey="fight_time_out_mod_bonus"
          name="fight_time_out_mod_bonus"
        />
        <x-form-elements.input
          :model="$item"
          label="Movement Timeout Modifier (%):"
          modelKey="move_time_out_mod_bonus"
          name="move_time_out_mod_bonus"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill Usage Bonus (%):"
          modelKey="increase_skill_bonus_by"
          name="increase_skill_bonus_by"
        />
        <x-form-elements.input
          :model="$item"
          label="Skill XP Bonus (%):"
          modelKey="increase_skill_training_bonus_by"
          name="increase_skill_training_bonus_by"
        />
      </x-form-wizard.step>
    </x-form-wizard.container>
  </x-core.layout.info-container>
@endsection
