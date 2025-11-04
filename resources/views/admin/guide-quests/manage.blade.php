@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-form-wizard.container
      totalSteps="13"
      name="{{ !is_null($guideQuest) ? 'Edit: ' . nl2br($guideQuest->name) : 'Create New Guide Quest' }}"
      homeRoute="{{ !is_null($guideQuest) ? route('admin.guide-quests.show', ['guideQuest' => $guideQuest->id]) : route('admin.guide-quests') }}"
      formAction="{{ route('admin.guide-quests.store') }}"
      modelId="{{ !is_null($guideQuest) ? $guideQuest->id : 0 }}"
    >
      <x-form-wizard.step stepTitle="Basic Info">
        <x-form-elements.input
          :model="$guideQuest"
          label="Name:"
          modelKey="name"
          name="name"
        />
        <x-form-elements.quill-editor type="normal" :model="$guideQuest" label="Guide Text:"
                                   modelKey="intro_text" name="intro_text" quillId="intro-text" />
        <x-form-elements.quill-editor type="html" :model="$guideQuest" label="Instructions:"
                                   modelKey="instructions" name="instructions" quillId="quest-instructions" />
        <x-form-elements.quill-editor type="html" :model="$guideQuest" label="Desktop Instructions:"
                                   modelKey="desktop_instructions" name="desktop_instructions" quillId="desktop-instructions" />
        <x-form-elements.quill-editor type="html" :model="$guideQuest" label="Mobile Instructions:"
                                   modelKey="mobile_instructions" name="mobile_instructions" quillId="mobile-instructions" />

        <x-core.separator.separator />
        <h2 class="mb-4 text-lg text-center">Appear During</h2>
        <x-core.separator.separator />
        
        <p class="mb-3">
          When setting these values, these guide quests will jump in
          regardless of where the player is In their set of guide quests,
          these will over ride those and make the player do the quests going
          down.
        </p>
        <p class="mb-3">
          The quests that use these should be in the order of Parent which
          unlocks during an event and/or at a specific level and then any
          additional guide quests that need to explain the specific feature
          or features would set that quest as their parent.
        </p>
        <p class="mb-3">
          Once the quests are done in the parent line then the player is
          returned to the original set of guide quests.
        </p>
        <x-form-elements.input
          :model="$guideQuest"
          label="Only At Level:"
          modelKey="unlock_at_level"
          name="unlock_at_level"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Only During Event:"
          modelKey="only_during_event"
          name="only_during_event"
          :options="$events"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Belongs to parent:"
          modelKey="parent_id"
          name="parent_id"
          :options="$guideQuests"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Level Requirements">
        <x-form-elements.input
          :model="$guideQuest"
          label="Required (Player) Level:"
          modelKey="required_level"
          name="required_level"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Skill:"
          modelKey="required_skill"
          name="required_skill"
          :options="$gameSkills"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required (Skill) Level:"
          modelKey="required_skill_level"
          name="required_skill_level"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Secondary Required Skill (optional):"
          modelKey="required_secondary_skill"
          name="required_secondary_skill"
          :options="$gameSkills"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required (Secondary Skill) Level (Optional):"
          modelKey="required_secondary_skill_level"
          name="required_secondary_skill_level"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Skill Type (optional):"
          modelKey="required_skill_type"
          name="required_skill_type"
          :options="$skillTypes"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Skill Type Level (Optional):"
          modelKey="required_skill_type_level"
          name="required_skill_type_level"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Faction Requirements">
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Faction:"
          modelKey="required_faction_id"
          name="required_faction_id"
          :options="$factionMaps"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required (Faction) Level:"
          modelKey="required_faction_level"
          name="required_faction_level"
        />

        <x-form-elements.input
          :model="$guideQuest"
          label="Required Fame Level"
          modelKey="required_fame_level"
          name="required_fame_level"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Currency Requirements">
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Gold"
          modelKey="required_gold"
          name="required_gold"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Gold Dust"
          modelKey="required_gold_dust"
          name="required_gold_dust"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Shards"
          modelKey="required_shards"
          name="required_shards"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Copper Coins"
          modelKey="required_copper_coins"
          name="required_copper_coins"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Gold Bars"
          modelKey="required_gold_bars"
          name="required_gold_bars"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Stat Requirements">
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Stats (Total):"
          modelKey="required_stats"
          name="required_stats"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Strength (Total):"
          modelKey="required_str"
          name="required_str"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Dexterity (Total):"
          modelKey="required_dex"
          name="required_dex"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Intelligence (Total):"
          modelKey="required_int"
          name="required_int"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Agility (Total):"
          modelKey="required_agi"
          name="required_agi"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Charisma (Total):"
          modelKey="required_chr"
          name="required_chr"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Durability (Total):"
          modelKey="required_dur"
          name="required_dur"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Focus (Total):"
          modelKey="required_focus"
          name="required_focus"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Kingdom Requirements">
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Kingdoms #:"
          modelKey="required_kingdoms"
          name="required_kingdoms"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Kingdom Level:"
          modelKey="required_kingdom_level"
          name="required_kingdom_level"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Building Level:"
          modelKey="required_kingdom_building_id"
          name="required_kingdom_building_id"
          :options="$kingdomBuildings"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Building Level:"
          modelKey="required_kingdom_building_level"
          name="required_kingdom_building_level"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Kingdom Units:"
          modelKey="required_kingdom_units"
          name="required_kingdom_units"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Passive:"
          modelKey="required_passive_skill"
          name="required_passive_skill"
          :options="$passives"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Passive Level:"
          modelKey="required_passive_level"
          name="required_passive_level"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Item Requirements">
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Specialty Type:"
          modelKey="required_specialty_type"
          name="required_specialty_type"
          :options="$itemSpecialtyTypes"
        />

        <x-form-elements.input
          :model="$guideQuest"
          label="Required Holy Stacks Applied:"
          modelKey="required_holy_stacks"
          name="required_holy_stacks"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Quest and Plane Requirements">
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Plane Access:"
          modelKey="required_game_map_id"
          name="required_game_map_id"
          :options="$gameMaps"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Quest (Complete):"
          modelKey="required_quest_id"
          name="required_quest_id"
          :options="$quests"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Required Quest Item:"
          modelKey="required_quest_item_id"
          name="required_quest_item_id"
          :options="$questItems"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Secondary Quest Item:"
          modelKey="secondary_quest_item_id"
          name="secondary_quest_item_id"
          :options="$questItems"
        />
        <x-form-elements.key-value-select
          :model="$guideQuest"
          label="Must be on Map:"
          modelKey="be_on_game_map"
          name="be_on_game_map"
          :options="$gameMaps"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Class Special Requirements">
        <x-form-elements.input
          :model="$guideQuest"
          label="Required # of class specials equipped:"
          modelKey="required_class_specials_equipped"
          name="required_class_specials_equipped"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Class Rank Level:"
          modelKey="required_class_rank_level"
          name="required_class_rank_level"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Global Event Goal Requirements">
        <x-form-elements.input
          :model="$guideQuest"
          label="Required Event Goal Kills:"
          modelKey="required_event_goal_participation"
          name="required_event_goal_participation"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Extra Bonuses">
        <x-form-elements.input
          :model="$guideQuest"
          label="Extra Faction Points Per Kill #:"
          modelKey="faction_points_per_kill"
          name="faction_points_per_kill"
        />
      </x-form-wizard.step>
      <x-form-wizard.step step-title="Rewards">
        <x-form-elements.input
          :model="$guideQuest"
          label="Gold Reward:"
          modelKey="gold_reward"
          name="gold_reward"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Gold Dust Reward:"
          modelKey="gold_dust_reward"
          name="gold_dust_reward"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="Shards Reward:"
          modelKey="shards_reward"
          name="shards_reward"
        />
        <x-form-elements.input
          :model="$guideQuest"
          label="XP Reward:"
          modelKey="xp_reward"
          name="xp_reward"
        />
      </x-form-wizard.step>
    </x-form-wizard.container>
  </x-core.layout.info-container>
@endsection
