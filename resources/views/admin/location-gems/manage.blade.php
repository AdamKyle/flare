@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ !is_null($gameLocationGemParamter) ? 'Edit: ' . $gameLocationGemParamter->name : 'Create Location Gem Parameters' }}"
            buttons="true"
            :back-url="!is_null($gameLocationGemParamter) ? route('admin.location-gems.show', ['gameLocationGemParamter' => $gameLocationGemParamter]) : route('admin.location-gems.list')"
        >
            <x-core.form-wizard.container
                action="{{ route('admin.location-gems.store') }}"
                modelId="{{ !is_null($gameLocationGemParamter) ? $gameLocationGemParamter->id : 0 }}"
                lastTab="location-gems-tab-5"
            >
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="location-gems-tab-1" primaryTitle="Basic Info" secondaryTitle="Name, location, and atonement" isActive="true" />
                    <x-core.form-wizard.tab target="location-gems-tab-2" primaryTitle="Player Rewards" secondaryTitle="Experience and faction ranges" />
                    <x-core.form-wizard.tab target="location-gems-tab-3" primaryTitle="Currency and Drops" secondaryTitle="Currency and item ranges" />
                    <x-core.form-wizard.tab target="location-gems-tab-4" primaryTitle="Enemy Combat" secondaryTitle="Enemy modifier ranges" />
                    <x-core.form-wizard.tab target="location-gems-tab-5" primaryTitle="Monster Rewards" secondaryTitle="Monster reward ranges" />
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="location-gems-tab-1" isOpen="true">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Identity</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Name this setup, select its location, and describe its intended use.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Name" modelKey="name" name="name" required autocomplete="off" />
                                <x-core.forms.collection-select :model="$gameLocationGemParamter" label="Location" modelKey="location_id" name="location_id" key="name_with_plane_for_location_gem" value="id" :options="$locations" required />
                                <div class="md:col-span-2">
                                    <x-core.forms.text-area
                                        :model="$gameLocationGemParamter"
                                        label="Description"
                                        modelKey="description"
                                        name="description"
                                        placeholder="Describe what this location gem parameter setup is for."
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Atonement</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store the selected monster atonement type and its future percentage range.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.key-value-select :model="$gameLocationGemParamter" label="Monster Atonement" modelKey="monster_atonement" name="monster_atonement" :options="$gemTypes" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Monster Atonement Range" modelKey="monster_atonement_range" name="monster_atonement_range" placeholder="0.01-1.0" />
                            </div>
                        </section>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="location-gems-tab-2">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Character Progression</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Configure future character experience and faction reward percentages.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Character XP Bonus Range" modelKey="character_xp_bonus_range" name="character_xp_bonus_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Character Class Rank XP Bonus Range" modelKey="character_class_rank_xp_bonus_range" name="character_class_rank_xp_bonus_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Character Class Specialty XP Gain Range" modelKey="character_class_specialty_xp_gain_range" name="character_class_specialty_xp_gain_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Faction Point Increase Range" modelKey="faction_point_increase_range" name="faction_point_increase_range" placeholder="0.01-1.0" />
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Skill Training</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Select crafting skills and store future training modifier percentages.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Kingdom Passive Training Reduction Range" modelKey="kingdom_passive_training_reduction_range" name="kingdom_passive_training_reduction_range" placeholder="0.01-1.0" />
                                <x-core.forms.collection-select-no-model
                                    label="Crafting Skills"
                                    name="crafting_skill_ids[]"
                                    key="name"
                                    value="id"
                                    :options="$craftingSkills"
                                    :relationIds="is_null($gameLocationGemParamter) ? [] : $gameLocationGemParamter->crafting_skill_ids"
                                />
                                <x-core.forms.input
                                    :model="$gameLocationGemParamter"
                                    label="Crafting Skill Bonus Range"
                                    modelKey="crafting_skill_bonus_range"
                                    name="crafting_skill_bonus_range"
                                    placeholder="0.01-1.0"
                                    helpText="Optional decimal percentage range applied later to selected crafting skill training and skill XP gained."
                                />
                            </div>
                        </section>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="location-gems-tab-3">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Currency</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future currency gain percentage ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Gold Gain Range" modelKey="gold_gain_range" name="gold_gain_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Gold Dust Gain Range" modelKey="gold_dust_gain_range" name="gold_dust_gain_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Shards Gain Range" modelKey="shards_gain_range" name="shards_gain_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Copper Coin Gain Range" modelKey="copper_coin_gain_range" name="copper_coin_gain_range" placeholder="0.01-1.0" />
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Item Drops</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future general and high-rarity item drop percentage ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Item Drop Chance Increase Range" modelKey="item_drop_chance_increase_range" name="item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Unique Item Drop Chance Increase Range" modelKey="unique_item_drop_chance_increase_range" name="unique_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Mythic Item Drop Chance Increase Range" modelKey="mythic_item_drop_chance_increase_range" name="mythic_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Cosmic Item Drop Chance Increase Range" modelKey="cosmic_item_drop_chance_increase_range" name="cosmic_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Ascended Item Drop Chance Increase Range" modelKey="ascended_item_drop_chance_increase_range" name="ascended_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                            </div>
                        </section>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="location-gems-tab-4">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Enemy Power</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future enemy strength and healing percentage ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input
                                    :model="$gameLocationGemParamter"
                                    label="Enemy Strength Increase Range"
                                    modelKey="enemy_strength_increase_range"
                                    name="enemy_strength_increase_range"
                                    placeholder="0.01-1.0"
                                    helpText="This future modifier only affects monster stats, health range, attack range, spell damage, and affix damage."
                                />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Healing Increase Range" modelKey="enemy_healing_increase_range" name="enemy_healing_increase_range" placeholder="0.01-1.0" />
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Enemy Avoidance and Resistance</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future enemy avoidance, resistance, and entrancing percentage ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Spell Evasion Range" modelKey="enemy_spell_evasion_range" name="enemy_spell_evasion_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Affix Resistance Range" modelKey="enemy_affix_resistance_range" name="enemy_affix_resistance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Entrancing Chance Range" modelKey="enemy_entrancing_chance_range" name="enemy_entrancing_chance_range" placeholder="0.01-1.0" />
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Enemy Special Chances</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future enemy special-action chance and resistance ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Devouring Light Chance Range" modelKey="enemy_devouring_light_chance_range" name="enemy_devouring_light_chance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Devouring Darkness Chance Range" modelKey="enemy_devouring_darkness_chance_range" name="enemy_devouring_darkness_chance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Ambush Chance Range" modelKey="enemy_ambush_chance_range" name="enemy_ambush_chance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Ambush Resistance Range" modelKey="enemy_ambush_resistance_range" name="enemy_ambush_resistance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Counter Chance Range" modelKey="enemy_counter_chance_range" name="enemy_counter_chance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Counter Resistance Range" modelKey="enemy_counter_resistance_range" name="enemy_counter_resistance_range" placeholder="0.01-1.0" />
                            </div>
                        </section>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="location-gems-tab-5">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Monster Rewards</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future monster quest item, experience, and gold reward ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Enemy Quest Item Drop Chance Increase Range" modelKey="enemy_quest_item_drop_chance_increase_range" name="enemy_quest_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Monster XP Increase Range" modelKey="monster_xp_increase_range" name="monster_xp_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameLocationGemParamter" label="Monster Gold Drop Increase Range" modelKey="monster_gold_drop_increase_range" name="monster_gold_drop_increase_range" placeholder="0.01-1.0" />
                            </div>
                        </section>
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
