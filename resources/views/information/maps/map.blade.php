@extends('layouts.information', [
    'pageTitle' => 'Location'
])

@section('content')
    <div class="row page-titles mt-3">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$map->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    <div class="mt-3">
        <x-cards.card>
            <div class="row">
                <div class="col-md-6 text-center mb-3">
                    <img src="{{$mapUrl}}" width="500" class="rounded img-fluid"/>
                </div>
                <div class="col-md-6 mb-3">
                    <h3>Map Bonuses</h3>
                    <dl>
                        <dt>XP Bonus</dt>
                        <dd>{{is_null($map->xp_bonus) ? 0 : $map->xp_bonus * 100}}%</dd>
                        <dt>Skill XP Bonus</dt>
                        <dd>{{is_null($map->skill_training_bonus) ? 0 : $map->skill_training_bonus * 100}}%</dd>
                        <dt>drop Chance Bonus</dt>
                        <dd>{{is_null($map->drop_chance_bonus) ? 0 : $map->drop_chance_bonus * 100}}%</dd>
                        <dt>Enemy Stat Increase</dt>
                        <dd>{{is_null($map->enemy_stat_bonus) ? 0 : $map->enemy_stat_bonus * 100}}%</dd>
                    </dl>
                    <p class="mt-3">
                        These bonuses will apply to adventures as well - thus stacking with the adventure bonuses.
                    </p>
                </div>
            </div>
            @if (!is_null($itemNeeded))
                <hr />
                <h3>Item required for access</h3>
                <hr />
                <p class="mt-3 mb-2">
                    In order to access this plane, you will need to have the following quest item:
                </p>
                <ul>
                    <li>
                        <a href="{{route('info.page.item', ['item' => $itemNeeded])}}">
                            <x-item-display-color :item="$itemNeeded" />
                        </a>
                    </li>
                </ul>
            @endif
            <h3>Monsters</h3>
            <hr />
            @livewire('admin.monsters.data-table', [
                'onlyMapName' => $map->name,
                'withCelestials' => false,
            ])
            <h3>Celestials</h3>
            <hr />
            @livewire('admin.monsters.data-table', [
                'onlyMapName' => $map->name,
                'withCelestials' => true,
            ])
            <h3>NPC's</h3>
            <hr />
            @livewire('admin.npcs.data-table', [
                'forMap' => $map->id,
            ])
            <h3>Quests</h3>
            <hr />
            @livewire('admin.quests.data-table', [
                'forMap' => $map->id,
            ])
            <h3>Adventures</h3>
            <hr />
            @livewire('admin.adventures.data-table', [
                'gameMapId' => $map->id,
            ])
            <h3>Locations</h3>
            <hr />
            @livewire('admin.locations.data-table', [
                'gameMapId' => $map->id,
            ])
        </x-cards.card>
    </div>
@endsection
