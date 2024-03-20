@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{'Manage '.$gameMap->name.' Bonuses'}}"
            buttons="true"
            backUrl="{{route('map', ['gameMap' => $gameMap->id])}}"
        >
            <form class="mt-4" action="{{route('add.map.bonuses', ['gameMap' => $gameMap])}}" method="POST">
                @csrf

                <x-core.forms.input :model="$gameMap" label="XP Bonus (%):" modelKey="xp_bonus" name="xp_bonus" type="number"/>
                <x-core.forms.input :model="$gameMap" label="Skill Training Bonus (%):" modelKey="skill_training_bonus" name="skill_training_bonus" type="number"/>
                <x-core.forms.input :model="$gameMap" label="Drop Chance Bonus (%):" modelKey="drop_chance_bonus" name="drop_chance_bonus" type="number"/>
                <x-core.forms.input :model="$gameMap" label="Enemy Stat increase (%):" modelKey="enemy_stat_bonus" name="enemy_stat_bonus" type="number"/>
                <x-core.forms.input :model="$gameMap" label="Character Attack Reduction (%):" modelKey="character_attack_reduction" name="character_attack_reduction" type="number"/>
                <x-core.forms.collection-select :model="$gameMap" label="Required Location:" modelKey="required_location_id" name="required_location_id" value="id" key="name" :options="$locations" />
                <x-core.forms.check-box :model="$gameMap" label="Can Traverse" modelKey="can_traverse"
                                        name="can_traverse" />
                <x-core.buttons.primary-button type="submit">
                    Submit
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
