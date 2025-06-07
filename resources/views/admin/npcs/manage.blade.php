@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{is_null($npc) ? 'Create NPC' : 'Edit '.$npc->name}}"
            buttons="true"
            backUrl="{{route('npcs.index')}}"
        >
            <form class="mt-4" action="{{ route('npc.store') }}" method="POST">
                @csrf

                <input
                    type="hidden"
                    value="{{ is_null($npc) ? '' : $npc->id }}"
                    name="npc_id"
                />

                <x-core.forms.input
                    :model="$npc"
                    label="Name:"
                    modelKey="real_name"
                    name="real_name"
                />
                <x-core.forms.key-value-select
                    :model="$npc"
                    label="Lives on map:"
                    modelKey="game_map_id"
                    name="game_map_id"
                    :options="$gameMaps"
                />
                <x-core.forms.key-value-select
                    :model="$npc"
                    label="NPC Type:"
                    modelKey="type"
                    name="type"
                    :options="$types"
                />
                <x-core.forms.input
                    :model="$npc"
                    label="X Position:"
                    modelKey="x_position"
                    name="x_position"
                />
                <x-core.forms.input
                    :model="$npc"
                    label="Y Position:"
                    modelKey="y_position"
                    name="y_position"
                />

                <x-core.buttons.primary-button type="submit">
                    Submit
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
