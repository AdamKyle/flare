@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="{{!is_null($item) ? 'Edit: ' . nl2br($item->name) : 'Create New Item'}}"
      buttons="true"
      backUrl="{{!is_null($item) ? route('items.item', ['item' => $item->id]) : route('items.list')}}"
    >
      <x-core.form-wizard.container
        action="{{route('item.store')}}"
        modelId="{{!is_null($item) ? $item->id : 0}}"
        lastTab="tab-style-2-5"
      >
        <x-core.form-wizard.tabs>
          <x-core.form-wizard.tab
            target="tab-style-2-1"
            primaryTitle="Basic Info"
            secondaryTitle="Basic information about the item."
            isActive="true"
          />
          <x-core.form-wizard.tab
            target="tab-style-2-2"
            primaryTitle="Stats"
            secondaryTitle="Set up the stat data for the item."
          />
          <x-core.form-wizard.tab
            target="tab-style-2-3"
            primaryTitle="Modifiers"
            secondaryTitle="Modifiers that effect the character."
          />
          <x-core.form-wizard.tab
            target="tab-style-2-4"
            primaryTitle="Crafting"
            secondaryTitle="Crafting Details."
          />
          <x-core.form-wizard.tab
            target="tab-style-2-5"
            primaryTitle="Quest"
            secondaryTitle="Quest Details"
          />
          <x-core.form-wizard.tab
            target="tab-style-2-6"
            primaryTitle="Usability"
            secondaryTitle="Usable Details."
          />
        </x-core.form-wizard.tabs>
        <x-core.form-wizard.contents>
          <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
            <div class="grid gap-2 md:grid-cols-2">
              <div>
                <h3 class="mb-3">Basic Item Info</h3>
                <x-core.forms.input
                  :model="$item"
                  label="Name:"
                  modelKey="name"
                  name="name"
                />
                <x-core.forms.select
                  :model="$item"
                  label="Type:"
                  modelKey="type"
                  name="type"
                  :options="$types"
                />
                <x-core.forms.text-area
                  :model="$item"
                  label="Description:"
                  modelKey="description"
                  name="description"
                />
                <x-core.forms.select
                  :model="$item"
                  label="Default Position (Armour only):"
                  modelKey="default_position"
                  name="default_position"
                  :options="$defaultPositions"
                />
              </div>
              <div
                class="my-6 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
              ></div>
              <div>
                <h3 class="mb-3">Item Cost Info</h3>
                <x-core.forms.check-box
                  :model="$item"
                  label="Can list on market?"
                  modelKey="market_sellable"
                  name="market_sellable"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Gold Cost:"
                  modelKey="cost"
                  name="cost"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Shards Cost:"
                  modelKey="shards_cost"
                  name="shards_cost"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Gold Dust Cost:"
                  modelKey="gold_dust_cost"
                  name="gold_dust_cost"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Copper Coin Cost:"
                  modelKey="copper_coin_cost"
                  name="copper_coin_cost"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Gold Bars Cost:"
                  modelKey="gold_bars_cost"
                  name="gold_bars_cost"
                />
              </div>
            </div>
          </x-core.form-wizard.content>
          <x-core.form-wizard.content target="tab-style-2-2">
            <div class="grid md:grid-cols-2 md:gap-3">
              <div>
                <h3 class="mb-3">Stat Info</h3>
                <x-core.forms.input
                  :model="$item"
                  label="Str Modifier (%):"
                  modelKey="str_mod"
                  name="str_mod"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Dex Modifier (%):"
                  modelKey="dex_mod"
                  name="dex_mod"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Dur Modifier (%):"
                  modelKey="dur_mod"
                  name="dur_mod"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Agi Modifier (%):"
                  modelKey="agi_mod"
                  name="agi_mod"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Int Modifier (%):"
                  modelKey="int_mod"
                  name="int_mod"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Chr Modifier (%):"
                  modelKey="chr_mod"
                  name="chr_mod"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Focus Modifier (%):"
                  modelKey="focus_mod"
                  name="focus_mod"
                />
              </div>
              <div
                class="my-6 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
              ></div>
              <div>
                <h3 class="mb-3">Attack/Def/Healing</h3>
                <x-core.forms.input
                  :model="$item"
                  label="Base Attack:"
                  modelKey="base_damage"
                  name="base_damage"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Base AC:"
                  modelKey="base_ac"
                  name="base_ac"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Base healing:"
                  modelKey="base_healing"
                  name="base_healing"
                />
              </div>
            </div>
          </x-core.form-wizard.content>
          <x-core.form-wizard.content target="tab-style-2-3">
            <div class="grid md:grid-cols-2 md:gap-3">
              <div>
                <h3 class="mb-3">Modifiers</h3>
                <x-core.forms.input
                  :model="$item"
                  label="Base Attack Mod (%):"
                  modelKey="base_damage_mod"
                  name="base_damage_mod"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Base AC Mod (%):"
                  modelKey="base_ac_mod"
                  name="base_ac_mod"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Base healing Mod (%):"
                  modelKey="base_healing_mod"
                  name="base_healing_mod"
                />
                <div
                  class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
                ></div>

                <h3 class="mb-3">Resurrection Chance</h3>
                <x-core.forms.check-box
                  :model="$item"
                  label="Can Ressurect?"
                  modelKey="can_resurrect"
                  name="can_resurrect"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Ressuectrion Chance (%):"
                  modelKey="resurrection_chance"
                  name="resurrection_chance"
                />

                <div
                  class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
                ></div>
                <h3 class="mb-3">Xp Modifiers</h3>
                <x-core.forms.input
                  :model="$item"
                  label="XP Bonus (%):"
                  modelKey="xp_bonus"
                  name="xp_bonus"
                />
                <x-core.forms.check-box
                  :model="$item"
                  label="Can Ignore Caps?"
                  modelKey="ignores_caps"
                  name="ignores_caps"
                />
              </div>
              <div
                class="my-6 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
              ></div>
              <div>
                <h3 class="mb-3">Enemy Reductions</h3>
                <x-core.forms.input
                  :model="$item"
                  label="Spell Evasion (%):"
                  modelKey="spell_evasion"
                  name="spell_evasion"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Artifact Annulment (%):"
                  modelKey="artifact_annulment"
                  name="artifact_annulment"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Affix Damage Reduction (%):"
                  modelKey="affix_damage_reduction"
                  name="affix_damage_reduction"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Healing Reduction (%):"
                  modelKey="healing_reduction"
                  name="healing_reduction"
                />

                <div
                  class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
                ></div>
                <h3 class="mb-3">Devouring Light/Darkness</h3>
                <x-core.forms.input
                  :model="$item"
                  label="Devouring Light Chance (%):"
                  modelKey="devouring_light"
                  name="devouring_light"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Devouring Darkness Chance (%):"
                  modelKey="devouring_darkness"
                  name="devouring_darkness"
                />
              </div>
            </div>
          </x-core.form-wizard.content>
          <x-core.form-wizard.content target="tab-style-2-4">
            <h3 class="mb-3">Crafting Info</h3>
            <x-core.forms.check-box
              :model="$item"
              label="Can Craft?"
              modelKey="can_craft"
              name="can_craft"
            />
            <x-core.forms.check-box
              :model="$item"
              label="Can Only Craft?"
              modelKey="craft_only"
              name="craft_only"
            />
            <x-core.forms.select
              :model="$item"
              label="Crafting Type:"
              modelKey="crafting_type"
              name="crafting_type"
              :options="$craftingTypes"
            />
            <x-core.forms.input
              :model="$item"
              label="Skill Level Required:"
              modelKey="skill_level_required"
              name="skill_level_required"
            />
            <x-core.forms.input
              :model="$item"
              label="Skill Level Trivial:"
              modelKey="skill_level_trivial"
              name="skill_level_trivial"
            />

            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Holy Level</h3>
            <x-core.forms.input
              :model="$item"
              label="Holy Level:"
              modelKey="holy_level"
              name="holy_level"
            />
          </x-core.form-wizard.content>
          <x-core.form-wizard.content target="tab-style-2-5">
            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Effects</h3>
            <x-core.forms.select
              :model="$item"
              label="Effects (Quest items only):"
              modelKey="effect"
              name="effect"
              :options="$effects"
            />

            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Class to unlock</h3>
            <x-core.forms.key-value-select
              :model="$item"
              label="Unlocks Class (Quest items only)"
              modelKey="unlocks_class_id"
              name="unlocks_class_id"
              :options="$classes"
            />

            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Specialty Type</h3>
            <x-core.forms.select
              :model="$item"
              label="Specialty Type:"
              modelKey="specialty_type"
              name="specialty_type"
              :options="$specialtyTypes"
            />

            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Skill Tree (Parent Skill)</h3>
            <x-core.forms.collection-select
              :model="$item"
              label="Skill Tree:"
              modelKey="item_skill_id"
              name="item_skill_id"
              value="id"
              key="name"
              :options="$itemSkills"
            />

            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Drop Location</h3>
            <x-core.forms.collection-select
              :model="$item"
              label="Drops From:"
              modelKey="drop_location_id"
              name="drop_location_id"
              value="id"
              key="name"
              :options="$locations"
            />

            <div
              class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <h3 class="mb-3">Effects Skill</h3>
            <x-core.forms.select
              :model="$item"
              label="Skill:"
              modelKey="skill_name"
              name="skill_name"
              :options="$skills"
            />
            <x-core.forms.input
              :model="$item"
              label="Skill Bonus:"
              modelKey="skill_bonus"
              name="skill_bonus"
            />
            <x-core.forms.input
              :model="$item"
              label="Skill Training Bonus:"
              modelKey="skill_training_bonus"
              name="skill_training_bonus"
            />
          </x-core.form-wizard.content>
          <x-core.form-wizard.content target="tab-style-2-6">
            <div class="grid gap-2 md:grid-cols-2">
              <div>
                <h3 class="mb-3">Basic Usable Info</h3>
                <x-core.forms.check-box
                  :model="$item"
                  label="Usable?"
                  modelKey="usable"
                  name="usable"
                />
                <x-core.forms.check-box
                  :model="$item"
                  label="Can use on items?"
                  modelKey="can_use_on_other_items"
                  name="can_use_on_other_items"
                />
                <x-core.forms.check-box
                  :model="$item"
                  label="Can stack (use on self)?"
                  modelKey="can_stack"
                  name="can_stack"
                />
                <x-core.forms.check-box
                  :model="$item"
                  label="Gains a level when leveling?"
                  modelKey="gains_additional_level"
                  name="gains_additional_level"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Lasts For (Minutes):"
                  modelKey="lasts_for"
                  name="lasts_for"
                />
                <x-core.forms.check-box
                  :model="$item"
                  label="Increases Stats?"
                  modelKey="stat_increase"
                  name="stat_increase"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Increases All Stats By (%):"
                  modelKey="increase_stat_by"
                  name="increase_stat_by"
                />
                <x-core.forms.check-box
                  :model="$item"
                  label="Damages Kingdoms?"
                  modelKey="damages_kingdoms"
                  name="damages_kingdoms"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Kingdom Damage:"
                  modelKey="kingdom_damage"
                  name="kingdom_damage"
                />
              </div>
              <div
                class="my-6 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
              ></div>
              <div>
                <h3>Skill Info</h3>
                <x-core.forms.key-value-select
                  :model="$item"
                  label="Type:"
                  modelKey="affects_skill_type"
                  name="affects_skill_type"
                  :options="$skillTypes"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Skill Damage Modifier (%):"
                  modelKey="base_damage_mod_bonus"
                  name="base_damage_mod_bonus"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Skill AC Modifier (%):"
                  modelKey="base_ac_mod_bonus"
                  name="base_ac_mod_bonus"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Skill Healing Modifier (%):"
                  modelKey="base_healing_mod_bonus"
                  name="base_healing_mod_bonus"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Fight Timeout Modifier (%):"
                  modelKey="fight_time_out_mod_bonus"
                  name="fight_time_out_mod_bonus"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Movement Timeout Modifier (%):"
                  modelKey="move_time_out_mod_bonus"
                  name="move_time_out_mod_bonus"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Skill Usage Bonus (%):"
                  modelKey="increase_skill_bonus_by"
                  name="increase_skill_bonus_by"
                />
                <x-core.forms.input
                  :model="$item"
                  label="Skill XP Bonus (%):"
                  modelKey="increase_skill_training_bonus_by"
                  name="increase_skill_training_bonus_by"
                />
              </div>
            </div>
          </x-core.form-wizard.content>
        </x-core.form-wizard.contents>
      </x-core.form-wizard.container>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
