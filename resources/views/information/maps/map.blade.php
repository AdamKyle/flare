@extends('layouts.information', [
    'pageTitle' => 'Location'
])

@section('content')
    <div class="w-full lg:w-3/5 m-auto mt-20 mb-10">
        <x-core.page-title
          title="{{$map->name}}"
          route="{{url()->previous()}}"
          link="Back"
          color="primary"
        ></x-core.page-title>
        <hr />
        <div class="mt-3">
            <x-core.cards.card>
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
                            <dt>Drop Chance Bonus</dt>
                            <dd>{{is_null($map->drop_chance_bonus) ? 0 : $map->drop_chance_bonus * 100}}%</dd>
                            <dt>Enemy Stat Increase</dt>
                            <dd>{{is_null($map->enemy_stat_bonus) ? 0 : $map->enemy_stat_bonus * 100}}%</dd>
                            <dt>Character Damage Deduction:</dt>
                            <dd>{{!is_null($map->character_attack_reduction) ? ($map->character_attack_reduction * 100) . '%' : '0%'}}</dd>
                            @if (!is_null($map->required_location_id))
                                <dt>Must be at location (X/Y):</dt>
                                <dd>{{$map->requiredLocation->x}}/{{$map->requiredLocation->y}}</dd>
                                <dt>On Plane:</dt>
                                <dd>{{$map->requiredLocation->map->name}}</dd>
                            @endif
                        </dl>
                        <p class="mt-3">
                            These bonuses will apply to adventures as well - thus stacking with the adventure bonuses.
                        </p>
                        @if ($map->mapType()->isShadowPlane())
                            <hr />
                            <h3>Tips</h3>
                            <p>
                                Do not underestimate enemies down here. The further down the list, the harder they get.
                                Your character should have a very good stat reduction (that effects all stats), skill reduction
                                and resistance reduction enchantments.
                            </p>
                            <p>
                                Without these, you may find it harder to hit top end creatures, depending on your level and gear.
                            </p>
                        @endif

                        @if ($map->mapType()->isHell())
                            <hr />
                            <h3>Caution</h3>
                            <p>
                                Enemies are increased by 75% in terms of stats and resistances. Characters will want top tier gear while down here as their modified
                                stats and their damage dealt are reduced by 60%. When characters enter they will see their modified stats adjust to reflect this.
                            </p>
                            <p>
                                Further complicating things, vampires damage is capped at 50% of their total life stealing %. Casters with out high resistance reduction and skill reduction gear
                                will find their spells are being evaded. Quest items which make your affixes irresistible no longer work down here.
                            </p>
                            <p>
                                Finally, enemies down here are different then other planes, they're stats start in the tens of millions and go up from there. Players wil also need to a quest line to walk on magma, unlock purgatory and speak with the Fabled
                                and illustrious Queen of Hearts.
                            </p>
                        @endif

                        @if ($map->mapType()->isPurgatory())
                        @endif
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
                <h3 class="mt-5">Celestials</h3>
                <hr />
                @livewire('admin.monsters.data-table', [
                    'onlyMapName' => $map->name,
                    'only' => 'celestials',
                ])
                <h3 class="mt-5">NPC's</h3>
                <hr />
                @livewire('admin.npcs.data-table', [
                    'forMap' => $map->id,
                ])
                <h3 class="mt-5">Quests</h3>
                <hr />
                @livewire('admin.quests.data-table', [
                    'forMap' => $map->id,
                ])
                <h3 class="mt-5">Adventures</h3>
                <hr />
                @livewire('admin.adventures.data-table', [
                    'gameMapId' => $map->id,
                ])
                <h3 class="mt-5">Locations</h3>
                <hr />
                @livewire('admin.locations.data-table', [
                    'gameMapId' => $map->id,
                ])
            </x-core.cards.card>
        </div>
    </div>
@endsection
