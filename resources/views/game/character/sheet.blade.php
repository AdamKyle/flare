@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Character Sheet"
        route="{{auth()->user()->hasRole('Admin') ? route('admin.character.modeling') : route('game')}}"
        link="{{auth()->user()->hasRole('Admin') ? 'Back' : 'Game'}}"
        color="{{auth()->user()->hasRole('Admin') ? 'success' : 'primary'}}"
    ></x-core.page-title>
    <hr />
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-6">
            <x-tabs.pill-tabs-container>
                <x-tabs.tab tab="info" title="Information" selected="true" active="true" />
                <x-tabs.tab tab="active-boons" title="Active Boons" selected="false" active="false" />
            </x-tabs.pill-tabs-container>
            <x-tabs.tab-content>
                <x-tabs.tab-content-section tab="info" active="true">
                    <x-cards.card>
                        <div class="row">
                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-6">
                                @include('game.character.partials.sheet.basic-information', ['character' => $character, 'maxLevel' => $maxLevel])
                            </div>

                            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-6">
                                @include('game.character.partials.sheet.attack-stats', ['character' => $character])
                            </div>
                        </div>
                        <hr />
                        <div class="row mt-2">
                            @include('game.character.partials.sheet.core-stats', ['character' => $character])
                        </div>
                        <hr />
                        <h5>Attack Break Down</h5>
                        <hr />
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <dl>
                                    <dt>Attack (includes rings + weapon):</dt>
                                    <dd>{{$character->getInformation()->buildAttack()}}</dd>
                                    <dt>Spell Damage:</dt>
                                    <dd>{{$character->getInformation()->getTotalSpellDamage()}}</dd>
                                    <dt>Artifact Damage:</dt>
                                    <dd>{{$character->getInformation()->getTotalArtifactDamage()}}</dd>
                                    <dt>Heal For:</dt>
                                    <dd>{{$character->getInformation()->buildHealFor()}}</dd>
                                </dl>
                            </div>
                        </div>
                    </x-cards.card>
                </x-tabs.tab-content-section>
                <x-tabs.tab-content-section tab="active-boons" active="false">
                    <div class="alert alert-info mt-2 mb-3">
                        Clicking on a row will allow you to see more details as well as cancel a boon.
                    </div>
                    <div id="active-boons" data-user="{{$character->user->id}}" data-character="{{$character->id}}"></div>
                </x-tabs.tab-content-section>
            </x-tabs.tab-content>

            <x-cards.card-with-title
                title="Skills"
                additionalClasses="character-skill-info"
            >
                @include('game.character.partials.sheet.admin.skill-change-notice')
                @include('game.character.partials.sheet.notices.adventuring', ['character' => $character])
                @foreach($character->skills->sortByDesc('can_train') as $skill)
                    @include('game.character.partials.sheet.skills.skill-section', [
                        'skill' => $skill,
                        'character' => $character
                    ])
                @endforeach
            </x-cards.card-with-title>

            @include('game.character.partials.sheet.admin.character-management')
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-6">
            <x-cards.card-with-title
                title="Inventory"
            >
                @include('game.character.partials.sheet.admin.change-equipment', ['character' => $character])

                <dl>
                    <dt>Total gold:</dt>
                    <dd>{{number_format($character->gold)}}</dd>
                    <dt>Total gold dust:</dt>
                    <dd>{{number_format($character->gold_dust)}}</dd>
                    <dt>Total shards:</dt>
                    <dd>{{number_format($character->shards)}}</dd>
                    <dt>Used / Max inventory space:</dt>
                    <dd>{{$character->inventory->slots->count()}} / {{$character->inventory_max}}</dd>
                    <dt>Stat to focus on for max damage:</dt>
                    <dd>{{$character->class->damage_stat}}</dd>
                </dl>
            </x-cards.card-with-title>
            <div class="mt-3">
                <x-tabs.pill-tabs-container>
                    <x-tabs.tab tab="inventory" title="Inventory" selected="true" active="true" />
                    <x-tabs.tab tab="equipped" title="Equipped" selected="false" active="false" />
                </x-tabs.pill-tabs-container>
                <x-tabs.tab-content>
                    <x-tabs.tab-content-section tab="inventory" active="true">
                        <div class="alert alert-info mb-2 mt-2">
                            <p>You can click on item names to learn more about the item. Quest items are used automatically.
                            For example, Books give xp bonuses and skill bonuses to specific skill automatically and other items such as Flask of Fresh Air
                            lets you walk on water. Check the effects section of the quest item to see what it effects if it is not a book.<p>
                            <p>Destroying enchanted item will yield between 1-25 gold dust per item. Clicking disenchant will yield between 1-150 gold dust per item.</p>
                        </div>
                        @livewire('character.inventory.data-table', [
                            'includeQuestItems'        => true,
                            'allowInventoryManagement' => true,
                            'character'                => $character,
                            'allowMassDestroy'         => true,
                        ])
                    </x-tabs.tab-content-section>
                    <x-tabs.tab-content-section tab="equipped">
                        @livewire('character.inventory.data-table', [
                            'includeEquipped'          => true,
                            'allowUnequipAll'          => true,
                            'allowInventoryManagement' => true,
                            'character'                => $character,
                        ])
                    </x-tabs.tab-content-section>
                </x-tabs.tab-content>
            </div>
        </div>
    </div>
@endSection

@push('scripts')
    <script>
        characterBoons('active-boons');
    </script>
@endpush
