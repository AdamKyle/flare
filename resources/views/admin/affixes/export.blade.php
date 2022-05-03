@extends('layouts.app')

@section('content')
    @php
    // TODO: Find a better place for this.
    $types = [
            'all'                      => 'All Affixes',
            'str_mod'                  => 'Raises STR',
            'int_mod'                  => 'Raises Int',
            'dex_mod'                  => 'Raises Dex',
            'chr_mod'                  => 'Raises Chr',
            'agi_mod'                  => 'Raises Agi',
            'focus_mod'                => 'Raises Focus',
            'dur_mod'                  => 'Raises Dur',
            'str_reduction'            => 'Str Reduction',
            'dur_reduction'            => 'Dur Reduction',
            'dex_reduction'            => 'Dex Reduction',
            'chr_reduction'            => 'Chr Reduction',
            'int_reduction'            => 'Int Reduction',
            'agi_reduction'            => 'Agi Reduction',
            'focus_reduction'          => 'Focus Reduction',
            'base_damage_mod'          => 'Base Damage',
            'base_ac_mod'              => 'Base AC',
            'base_healing_mod'         => 'Base Healing',
            'skill_training_bonus'     => 'Skill Training Bonus',
            'skill_bonus'              => 'Skill Bonus',
            'skill_reduction'          => 'Enemy Skill Reduction',
            'resistance_reduction'     => 'Resistance Reduction',
            'base_damage_mod_bonus'    => 'Skill Base Damage',
            'base_healing_mod_bonus'   => 'Skill Healing',
            'base_ac_mod_bonus'        => 'Skill AC',
            'fight_time_out_mod_bonus' => 'Fight Timeout Modifiers',
            'move_time_out_mod_bonus'  => 'Move Timeout Modifiers',
            'devouring_light'          => 'Devouring Light',
            'damage'                   => 'Damage',
            'irresistible_damage'      => 'Is Irresistible Damage?',
            'damage_can_stack'         => 'Stacking Damage',
            'Weapon Crafting'          => 'Weapon Crafting',
            'Armour Crafting'          => 'Armour Crafting',
            'Spell Crafting'           => 'Spell Crafting',
            'Ring Crafting'            => 'Ring Crafting',
            'Artifact Crafting'        => 'Artifact Crafting',
            'Enchanting'               => 'Enchanting',
            'Alchemy'                  => 'Alchemy',
            'Accuracy'                 => 'Accuracy',
            'Dodge'                    => 'Dodge',
            'Looting'                  => 'Looting',
            'Quick Feet'               => 'Quick Feet',
            'Casting Accuracy'         => 'Casting Accuracy',
            'Criticality'              => 'Criticality',
            'Kingmanship'              => 'Kingmanship',
            'Soldier\'s Strength'      => 'Soldier\'s Strength',
            'Shadow Dance'             => 'Shadow Dance',
            'Blood Lust'               => 'Blood Lust',
            'Nature\'s Insight'        => 'Nature\'s Insight',
            'Alchemist\'s Concoctions' => 'Alchemist\'s Concoctions',
            'Hell\'s Anvil'            => 'Hell\'s Anvil',
            'Celestial Prayer'         => 'Celestial Prayer',
            'Astral Magics'            => 'Astral Magics',
            'Fighter\'s Resilience'    => 'Fighter\'s Resilience',
        ];
    @endphp
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Affixes"
            buttons="true"
            backUrl="{{route('affixes.list')}}"
        >
            <form method="POST" action="{{ route('affixes.export-data') }}">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="export_type">Type to export</label>
                    <select class="form-control" name="export_type">
                        <option value="">Please select</option>
                        @foreach($types as $key => $value)
                            <option value={{$key}}>{{$value}}</option>
                        @endforeach
                    </select>
                </div>
                <x-core.buttons.primary-button type="submit">
                    Export
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
