@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ !is_null($gameMapGemParamters) ? 'Edit: ' . $gameMapGemParamters->name : 'Create Map Gem Parameters' }}"
            buttons="true"
            :back-url="!is_null($gameMapGemParamters) ? route('admin.map-gems.show', ['gameMapGemParamters' => $gameMapGemParamters]) : route('admin.map-gems.list')"
        >
            <x-core.form-wizard.container
                action="{{ route('admin.map-gems.store') }}"
                modelId="{{ !is_null($gameMapGemParamters) ? $gameMapGemParamters->id : 0 }}"
                lastTab="map-gems-tab-5"
            >
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="map-gems-tab-1" primaryTitle="Basic Info" secondaryTitle="Name, map, and atonement" isActive="true" />
                    <x-core.form-wizard.tab target="map-gems-tab-2" primaryTitle="Player Rewards" secondaryTitle="Experience and faction ranges" />
                    <x-core.form-wizard.tab target="map-gems-tab-3" primaryTitle="Currency and Drops" secondaryTitle="Currency and item ranges" />
                    <x-core.form-wizard.tab target="map-gems-tab-4" primaryTitle="Enemy Combat" secondaryTitle="Enemy modifier ranges" />
                    <x-core.form-wizard.tab target="map-gems-tab-5" primaryTitle="Monster Rewards" secondaryTitle="Monster reward ranges" />
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="map-gems-tab-1" isOpen="true">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Identity</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Name this setup, select its map, and describe its intended use.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameMapGemParamters" label="Name" modelKey="name" name="name" required autocomplete="off" />
                                <x-core.forms.collection-select :model="$gameMapGemParamters" label="Game Map" modelKey="game_map_id" name="game_map_id" key="name" value="id" :options="$gameMaps" required />
                                <div class="md:col-span-2">
                                    <x-core.forms.text-area
                                        :model="$gameMapGemParamters"
                                        label="Description"
                                        modelKey="description"
                                        name="description"
                                        placeholder="Describe what this map gem parameter setup is for."
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Atonement</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store the selected monster atonement type and its future percentage range.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.key-value-select :model="$gameMapGemParamters" label="Monster Atonement" modelKey="monster_atonement" name="monster_atonement" :options="$gemTypes" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Monster Atonement Range" modelKey="monster_atonement_range" name="monster_atonement_range" placeholder="0.01-1.0" />
                            </div>
                        </section>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="map-gems-tab-2">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Character Progression</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Configure future character experience and faction reward percentages.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameMapGemParamters" label="Character XP Bonus Range" modelKey="character_xp_bonus_range" name="character_xp_bonus_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Character Class Rank XP Bonus Range" modelKey="character_class_rank_xp_bonus_range" name="character_class_rank_xp_bonus_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Character Class Specialty XP Gain Range" modelKey="character_class_specialty_xp_gain_range" name="character_class_specialty_xp_gain_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Faction Point Increase Range" modelKey="faction_point_increase_range" name="faction_point_increase_range" placeholder="0.01-1.0" />
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Skill Training</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Select crafting skills and store future training modifier percentages.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameMapGemParamters" label="Kingdom Passive Training Reduction Range" modelKey="kingdom_passive_training_reduction_range" name="kingdom_passive_training_reduction_range" placeholder="0.01-1.0" />
                                <x-core.forms.collection-select-no-model
                                    label="Crafting Skills"
                                    name="crafting_skill_ids[]"
                                    key="name"
                                    value="id"
                                    :options="$craftingSkills"
                                    :relationIds="is_null($gameMapGemParamters) ? [] : $gameMapGemParamters->crafting_skill_ids"
                                />
                                <x-core.forms.input
                                    :model="$gameMapGemParamters"
                                    label="Crafting Skill Bonus Range"
                                    modelKey="crafting_skill_bonus_range"
                                    name="crafting_skill_bonus_range"
                                    placeholder="0.01-1.0"
                                    helpText="Optional decimal percentage range applied later to selected crafting skill training and skill XP gained."
                                />
                            </div>
                        </section>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="map-gems-tab-3">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Currency</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future currency gain percentage ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameMapGemParamters" label="Gold Gain Range" modelKey="gold_gain_range" name="gold_gain_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Gold Dust Gain Range" modelKey="gold_dust_gain_range" name="gold_dust_gain_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Shards Gain Range" modelKey="shards_gain_range" name="shards_gain_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Copper Coin Gain Range" modelKey="copper_coin_gain_range" name="copper_coin_gain_range" placeholder="0.01-1.0" />
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Item Drops</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future general and high-rarity item drop percentage ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameMapGemParamters" label="Item Drop Chance Increase Range" modelKey="item_drop_chance_increase_range" name="item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Unique Item Drop Chance Increase Range" modelKey="unique_item_drop_chance_increase_range" name="unique_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Mythic Item Drop Chance Increase Range" modelKey="mythic_item_drop_chance_increase_range" name="mythic_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Cosmic Item Drop Chance Increase Range" modelKey="cosmic_item_drop_chance_increase_range" name="cosmic_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Ascended Item Drop Chance Increase Range" modelKey="ascended_item_drop_chance_increase_range" name="ascended_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                            </div>
                        </section>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="map-gems-tab-4">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Enemy Power</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future enemy strength and healing percentage ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input
                                    :model="$gameMapGemParamters"
                                    label="Enemy Strength Increase Range"
                                    modelKey="enemy_strength_increase_range"
                                    name="enemy_strength_increase_range"
                                    placeholder="0.01-1.0"
                                    helpText="This future modifier only affects monster stats, health range, attack range, spell damage, and affix damage."
                                />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Healing Increase Range" modelKey="enemy_healing_increase_range" name="enemy_healing_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input
                                    :model="$gameMapGemParamters"
                                    label="Character Power Reduction Range"
                                    modelKey="character_power_reduction_range"
                                    name="character_power_reduction_range"
                                    placeholder="0.01-1.0"
                                    helpText="Map gem only. This future modifier reduces the character's power on the attached map."
                                />
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Enemy Avoidance and Resistance</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future enemy avoidance, resistance, and entrancing percentage ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Spell Evasion Range" modelKey="enemy_spell_evasion_range" name="enemy_spell_evasion_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Affix Resistance Range" modelKey="enemy_affix_resistance_range" name="enemy_affix_resistance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Entrancing Chance Range" modelKey="enemy_entrancing_chance_range" name="enemy_entrancing_chance_range" placeholder="0.01-1.0" />
                            </div>
                        </section>

                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Enemy Special Chances</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future enemy special-action chance and resistance ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Devouring Light Chance Range" modelKey="enemy_devouring_light_chance_range" name="enemy_devouring_light_chance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Devouring Darkness Chance Range" modelKey="enemy_devouring_darkness_chance_range" name="enemy_devouring_darkness_chance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Ambush Chance Range" modelKey="enemy_ambush_chance_range" name="enemy_ambush_chance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Ambush Resistance Range" modelKey="enemy_ambush_resistance_range" name="enemy_ambush_resistance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Counter Chance Range" modelKey="enemy_counter_chance_range" name="enemy_counter_chance_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Counter Resistance Range" modelKey="enemy_counter_resistance_range" name="enemy_counter_resistance_range" placeholder="0.01-1.0" />
                            </div>
                        </section>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="map-gems-tab-5">
                        <section class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Monster Rewards</h3>
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Store future monster quest item, experience, and gold reward ranges.</p>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-core.forms.input :model="$gameMapGemParamters" label="Enemy Quest Item Drop Chance Increase Range" modelKey="enemy_quest_item_drop_chance_increase_range" name="enemy_quest_item_drop_chance_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Monster XP Increase Range" modelKey="monster_xp_increase_range" name="monster_xp_increase_range" placeholder="0.01-1.0" />
                                <x-core.forms.input :model="$gameMapGemParamters" label="Monster Gold Drop Increase Range" modelKey="monster_gold_drop_increase_range" name="monster_gold_drop_increase_range" placeholder="0.01-1.0" />
                            </div>
                        </section>
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
