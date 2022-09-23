@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($monster) ? 'Edit: ' . nl2br($monster->name) : 'Create New Monster'}}"
            buttons="true"
            backUrl="{{!is_null($monster) ? route('monsters.monster', ['monster' => $monster->id]) : route('monsters.list')}}"
        >
            <x-core.form-wizard.container action="{{route('monster.store')}}" modelId="{{!is_null($monster) ? $monster->id : 0}}" lastTab="tab-style-2-5">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic monster info." isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Resistances" secondaryTitle="Resistances against player actions."/>
                    <x-core.form-wizard.tab target="tab-style-2-3" primaryTitle="Modifiers" secondaryTitle="Misc modifiers."/>
                    <x-core.form-wizard.tab target="tab-style-2-4" primaryTitle="Ambush & Counter" secondaryTitle="Ambush and Counter"/>
                    <x-core.form-wizard.tab target="tab-style-2-5" primaryTitle="Quest Item" secondaryTitle="Monsters quest item."/>
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <div class="grid md:grid-cols-2 gap-2">
                            <div>
                                <h3 class="mb-3">Basic Item Info</h3>
                                <x-core.forms.input :model="$monster" label="Name:" modelKey="name" name="name" type="text"/>
                                <x-core.forms.input :model="$monster" label="Max Level:" modelKey="max_level" name="max_level" />
                                <x-core.forms.collection-select :model="$monster" label="Live on map:" modelKey="game_map_id" name="game_map_id" value="id" key="name" :options="$gameMaps" />
                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Stat Info</h3>
                                <x-core.forms.select :model="$monster" label="Damage Stat:" modelKey="damage_stat" name="damage_stat" :options="['str', 'dex', 'agi', 'dur', 'int', 'chr', 'focus']" />
                                <x-core.forms.input :model="$monster" label="Str:" modelKey="str" name="str" />
                                <x-core.forms.input :model="$monster" label="Dex:" modelKey="dex" name="dex" />
                                <x-core.forms.input :model="$monster" label="Dur:" modelKey="dur" name="dur" />
                                <x-core.forms.input :model="$monster" label="Agi:" modelKey="agi" name="agi" />
                                <x-core.forms.input :model="$monster" label="Int:" modelKey="int" name="int" />
                                <x-core.forms.input :model="$monster" label="Chr:" modelKey="chr" name="chr" />
                                <x-core.forms.input :model="$monster" label="Focus:" modelKey="focus" name="focus" />
                            </div>
                            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <div>
                                <h3 class="mb-3">Details</h3>
                                <x-core.forms.input :model="$monster" label="Health Range:" modelKey="health_range" name="health_range"/>
                                <x-core.forms.input :model="$monster" label="Attack Range:" modelKey="attack_range" name="attack_range"/>
                                <x-core.forms.input :model="$monster" label="Armour Class:" modelKey="ac" name="ac"/>
                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Reward Info</h3>
                                <x-core.forms.input :model="$monster" label="XP Per Kill:" modelKey="xp" name="xp"/>
                                <x-core.forms.input :model="$monster" label="Gold Per Kill:" modelKey="gold" name="gold"/>
                                <x-core.forms.input :model="$monster" label="Drop Check (%):" modelKey="drop_check" name="drop_check"/>
                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Celestial Info</h3>
                                <x-core.forms.check-box :model="$monster" label="Is Celestial Entity?" modelKey="is_celestial_entity" name="is_celestial_entity" />
                                <x-core.forms.key-value-select :model="$monster" label="Celestial Type (Optional):" modelKey="celestial_type" name="celestial_type" :options="$celestialTypes" />
                                <x-core.forms.input :model="$monster" label="Gold Cost Per Summon:" modelKey="gold_cost" name="gold_cost"/>
                                <x-core.forms.input :model="$monster" label="Gold Dust Cost per Summon:" modelKey="gold_dust_cost" name="gold_dust_cost"/>
                                <x-core.forms.input :model="$monster" label="Shards Reward Per kill:" modelKey="shards" name="shards"/>
                            </div>
                        </div>
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-2">
                        <x-core.forms.input :model="$monster" label="Spell Evasion (%):" modelKey="spell_evasion" name="spell_evasion"/>
                        <x-core.forms.input :model="$monster" label="Affix Damage Resistance (%):" modelKey="affix_resistance" name="affix_resistance"/>
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-3">
                        <div>
                            <h3 class="mb-3">Casting Details</h3>
                            <x-core.forms.check-box :model="$monster" label="Can Cast?" modelKey="can_cast" name="can_cast" />
                            <x-core.forms.input :model="$monster" label="Max Cast Amount:" modelKey="max_spell_damage" name="max_spell_damage"/>
                        </div>

                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>

                        <div class="grid md:grid-cols-2 gap-2">
                            <div>
                                <h3 class="mb-3">Misc Modifiers</h3>
                                <x-core.forms.input :model="$monster" label="Max Affix Damage:" modelKey="max_affix_damage" name="max_affix_damage"/>
                                <x-core.forms.input :model="$monster" label="Healing (%):" modelKey="healing_percentage" name="healing_percentage"/>
                                <x-core.forms.input :model="$monster" label="Entrancing Chance (%):" modelKey="entrancing_chance" name="entrancing_chance"/>
                            </div>
                            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                            <div>
                                <h3 class="mb-3">Skill Details</h3>
                                <x-core.forms.input :model="$monster" label="Accuracy (%):" modelKey="accuracy" name="accuracy"/>
                                <x-core.forms.input :model="$monster" label="Casting Accuracy (%):" modelKey="casting_accuracy" name="casting_accuracy"/>
                                <x-core.forms.input :model="$monster" label="Dodge (%):" modelKey="dodge" name="dodge"/>
                                <x-core.forms.input :model="$monster" label="Criticality (%):" modelKey="criticality" name="criticality"/>
                            </div>
                        </div>

                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-4">
                        <h3 class="mb-3">Ambush and Counter</h3>
                        <x-core.forms.input :model="$monster" label="Ambush Chance (%):" modelKey="ambush_chance" name="ambush_chance"/>
                        <x-core.forms.input :model="$monster" label="Ambush Resistance (%):" modelKey="ambush_resistance" name="ambush_resistance"/>
                        <x-core.forms.input :model="$monster" label="Counter Chance (%):" modelKey="counter_chance" name="counter_chance"/>
                        <x-core.forms.input :model="$monster" label="Counter Resistance (%):" modelKey="counter_resistance" name="counter_resistance"/>
                    </x-core.form-wizard.content>
                    <x-core.form-wizard.content target="tab-style-2-5">
                        <x-core.forms.collection-select :model="$monster" label="Quest Item to Drop:" modelKey="quest_item_id" name="quest_item_id" value="id" key="affix_name" :options="$questItems" />
                        <x-core.forms.input :model="$monster" label="Quest item Drop Chance:" modelKey="quest_item_drop_chance" name="quest_item_drop_chance"/>
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
