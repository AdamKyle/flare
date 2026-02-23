@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ !is_null($guideQuest) ? 'Edit: ' . nl2br($guideQuest->name) : 'Create Guide Quest' }}" buttons="true"
            backUrl="{{ !is_null($guideQuest) ? route('admin.guide-quests.show', ['guideQuest' => $guideQuest->id]) : route('admin.guide-quests') }}">
            <x-core.form-wizard.container action="{{ route('admin.guide-quests.store') }}"
                modelId="{{ !is_null($guideQuest) ? $guideQuest->id : 0 }}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic quest info."
                        isActive="true" />
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Requirements"
                        secondaryTitle="Requirments" />
                    <x-core.form-wizard.tab target="tab-style-2-3" primaryTitle="Bonuses" secondaryTitle="Bonuses" />
                    <x-core.form-wizard.tab target="tab-style-2-4" primaryTitle="Rewards" secondaryTitle="Rewards" />
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <h3 class="mb-3">Basic Info</h3>
                        <x-core.forms.input :model="$guideQuest" label="Name:" modelKey="name" name="name" />
                        <x-core.forms.quill-editor type="normal" :model="$guideQuest" label="Guide Text:"
                            modelKey="intro_text" name="intro_text" quillId="intro-text" />
                        <x-core.forms.quill-editor type="html" :model="$guideQuest" label="Instructions:"
                            modelKey="instructions" name="instructions" quillId="quest-instructions" />
                        <x-core.forms.quill-editor type="html" :model="$guideQuest" label="Desktop Instructions:"
                            modelKey="desktop_instructions" name="desktop_instructions" quillId="desktop-instructions" />
                        <x-core.forms.quill-editor type="html" :model="$guideQuest" label="Mobile Instructions:"
                            modelKey="mobile_instructions" name="mobile_instructions" quillId="mobile-instructions" />

                        <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                        <h3 class="mb-3">Appear During</h3>
                        <p class="mb-3">When setting these values, these guide quests will jump in regardless of where the player is
                        In their set of guide quests, these will over ride those and make the player do the quests going down.</p>
                        <p class="mb-3">
                            The quests that use these should be in the order of Parent which unlocks during an event and/or at a specific level
                            and then any additional guide quests that need to explain the specific feature or features would set that quest as their parent.
                        </p>
                        <p class="mb-3">
                            Once the quests are done in the parent line then the player is returned to the original set of guide quests.
                        </p>
                        <x-core.forms.input :model="$guideQuest" label="Only At Level:" modelKey="unlock_at_level" name="unlock_at_level" />
                        <x-core.forms.key-value-select :model="$guideQuest" label="Only During Event:"
                                                       modelKey="only_during_event" name="only_during_event" :options="$events" />
                        <x-core.forms.key-value-select :model="$guideQuest" label="Belongs to parent:"
                                                       modelKey="parent_id" name="parent_id" :options="$guideQuests" />
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="tab-style-2-2">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <h3 class="mb-3">Required levels for completion</h3>
                                <x-core.forms.input :model="$guideQuest" label="Required (Player) Level:"
                                    modelKey="required_level" name="required_level" />
                                <x-core.forms.input :model="$guideQuest" label="Required (Player) Reincarnations:"
                                                    modelKey="required_reincarnation_amount" name="required_reincarnation_amount" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Skill:"
                                    modelKey="required_skill" name="required_skill" :options="$gameSkills" />
                                <x-core.forms.input :model="$guideQuest" label="Required (Skill) Level:"
                                    modelKey="required_skill_level" name="required_skill_level" />
                                <x-core.forms.key-value-select :model="$guideQuest"
                                    label="Secondary Required Skill (optional):" modelKey="required_secondary_skill"
                                    name="required_secondary_skill" :options="$gameSkills" />
                                <x-core.forms.input :model="$guideQuest" label="Required (Secondary Skill) Level (Optional):"
                                    modelKey="required_secondary_skill_level" name="required_secondary_skill_level" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Skill Type (optional):"
                                    modelKey="required_skill_type" name="required_skill_type" :options="$skillTypes" />
                                <x-core.forms.input :model="$guideQuest" label="Required Skill Type Level (Optional):"
                                    modelKey="required_skill_type_level" name="required_skill_type_level" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Faction Requirements</h3>

                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Faction:"
                                                               modelKey="required_faction_id" name="required_faction_id" :options="$factionMaps" />
                                <x-core.forms.input :model="$guideQuest" label="Required (Faction) Level:"
                                                    modelKey="required_faction_level" name="required_faction_level" />

                                <x-core.forms.input :model="$guideQuest" label="Required Fame Level"
                                                    modelKey="required_fame_level" name="required_fame_level" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Stat Requirements</h3>
                                <x-core.forms.input :model="$guideQuest" label="Required Stats (Total):"
                                    modelKey="required_stats" name="required_stats" />
                                <x-core.forms.input :model="$guideQuest" label="Required Strength (Total):"
                                    modelKey="required_str" name="required_str" />
                                <x-core.forms.input :model="$guideQuest" label="Required Dexterity (Total):"
                                    modelKey="required_dex" name="required_dex" />
                                <x-core.forms.input :model="$guideQuest" label="Required Intelligence (Total):"
                                    modelKey="required_int" name="required_int" />
                                <x-core.forms.input :model="$guideQuest" label="Required Agility (Total):"
                                    modelKey="required_agi" name="required_agi" />
                                <x-core.forms.input :model="$guideQuest" label="Required Charisma (Total):"
                                    modelKey="required_chr" name="required_chr" />
                                <x-core.forms.input :model="$guideQuest" label="Required Durability (Total):"
                                    modelKey="required_dur" name="required_dur" />
                                <x-core.forms.input :model="$guideQuest" label="Required Focus (Total):"
                                    modelKey="required_focus" name="required_focus" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'>
                                </div>
                                <h3 class="mb-3">Kingdom Requirements</h3>
                                <x-core.forms.input :model="$guideQuest" label="Required Kingdoms #:"
                                    modelKey="required_kingdoms" name="required_kingdoms" />
                                <x-core.forms.input :model="$guideQuest" label="Required Kingdom Level:"
                                    modelKey="required_kingdom_level" name="required_kingdom_level" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Building Level:"
                                    modelKey="required_kingdom_building_id" name="required_kingdom_building_id"
                                    :options="$kingdomBuildings" />
                                <x-core.forms.input :model="$guideQuest" label="Required Building Level:"
                                    modelKey="required_kingdom_building_level" name="required_kingdom_building_level" />
                                <x-core.forms.input :model="$guideQuest" label="Required Kingdom Units:"
                                    modelKey="required_kingdom_units" name="required_kingdom_units" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Passive:"
                                    modelKey="required_passive_skill" name="required_passive_skill" :options="$passives" />
                                <x-core.forms.input :model="$guideQuest" label="Required Passive Level:"
                                    modelKey="required_passive_level" name="required_passive_level" />
                            </div>

                            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>

                            <div>

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'>
                                </div>
                                <h3 class="mb-3">Item Requirements</h3>
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Specialty Type:"
                                                               modelKey="required_specialty_type" name="required_specialty_type"
                                                               :options="$itemSpecialtyTypes" />

                                <x-core.forms.input :model="$guideQuest" label="Required Holy Stacks Applied:"
                                                    modelKey="required_holy_stacks" name="required_holy_stacks" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'>
                                </div>

                                <h3 class="mb-3">Quest and Plane Requirements</h3>
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Plane Access:"
                                    modelKey="required_game_map_id" name="required_game_map_id" :options="$gameMaps" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Quest (Complete):"
                                    modelKey="required_quest_id" name="required_quest_id" :options="$quests" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Required Quest Item:"
                                    modelKey="required_quest_item_id" name="required_quest_item_id" :options="$questItems" />
                                <x-core.forms.key-value-select :model="$guideQuest" label="Secondary Quest Item:"
                                    modelKey="secondary_quest_item_id" name="secondary_quest_item_id"
                                    :options="$questItems" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'>
                                </div>

                                <h3 class="mb-3">Required Class Rank Details</h3>
                                <x-core.forms.input :model="$guideQuest" label="Required # of class specials equipped:"
                                    modelKey="required_class_specials_equipped" name="required_class_specials_equipped" />
                                <x-core.forms.input :model="$guideQuest" label="Required Class Rank Level:"
                                    modelKey="required_class_rank_level" name="required_class_rank_level" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'>
                                </div>

                                <h3 class="mb-3">Required Currency Amounts</h3>
                                <x-core.forms.input :model="$guideQuest" label="Required Gold" modelKey="required_gold"
                                    name="required_gold" />
                                <x-core.forms.input :model="$guideQuest" label="Required Gold Dust"
                                    modelKey="required_gold_dust" name="required_gold_dust" />
                                <x-core.forms.input :model="$guideQuest" label="Required Shards" modelKey="required_shards"
                                    name="required_shards" />
                                <x-core.forms.input :model="$guideQuest" label="Required Copper Coins" modelKey="required_copper_coins"
                                                    name="required_copper_coins" />
                                <x-core.forms.input :model="$guideQuest" label="Required Gold Bars"
                                    modelKey="required_gold_bars" name="required_gold_bars" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'>
                                </div>

                                <h3 class="mb-3">Required To Be (Physically) On Map</h3>
                                <x-core.forms.key-value-select :model="$guideQuest" label="Must be on Map:"
                                   modelKey="be_on_game_map" name="be_on_game_map"
                                   :options="$gameMaps" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'>
                                </div>

                                <h3 class="mb-3">Event Goal Participation</h3>
                                <x-core.forms.input :model="$guideQuest" label="Required Event Goal Kills:"
                                   modelKey="required_event_goal_participation"
                                   name="required_event_goal_participation" />

                                <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'>
                                </div>

                                <h3 class="mb-3">Delve Requirements</h3>

                                <x-core.forms.input :model="$guideQuest" label="Required Delve Survival Hour(s):"
                                                    modelKey="required_delve_survival_time" name="required_delve_survival_time" />
                                <x-core.forms.input :model="$guideQuest" label="Required Delve Pack Size:"
                                                    modelKey="required_delve_pack_size" name="required_delve_pack_size" />
                            </div>
                        </div>
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-3">
                        <h3 class="mb-3">Extra Faction Points Per Kill</h3>
                        <x-core.forms.input :model="$guideQuest" label="Extra Faction Points Per Kill #:"
                            modelKey="faction_points_per_kill" name="faction_points_per_kill" />
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-4">
                        <h3 class="mb-3">Rewards</h3>
                        <x-core.forms.input :model="$guideQuest" label="Gold Reward:" modelKey="gold_reward"
                            name="gold_reward" />
                        <x-core.forms.input :model="$guideQuest" label="Gold Dust Reward:" modelKey="gold_dust_reward"
                            name="gold_dust_reward" />
                        <x-core.forms.input :model="$guideQuest" label="Shards Reward:" modelKey="shards_reward"
                            name="shards_reward" />
                        <x-core.forms.input :model="$guideQuest" label="XP Reward:" modelKey="xp_reward"
                            name="xp_reward" />
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
