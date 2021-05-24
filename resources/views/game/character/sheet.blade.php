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
            <x-cards.card-with-title
                title="Character Info"
            >
                <div class="row">
                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-4">
                        @include('game.character.partials.sheet.basic-information', ['character' => $character, 'maxLevel' => $maxLevel])
                    </div>

                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-4">
                        @include('game.character.partials.sheet.attack-stats', ['character' => $character])
                    </div>

                    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 col-xl-4">
                        @include('game.character.partials.sheet.core-stats', ['character' => $character])
                    </div>
                </div>
            </x-cards.card-with-title>

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
                    <dd>{{$character->gold}}</dd>
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
                        @livewire('character.inventory.data-table', [
                            'includeQuestItems'        => true,
                            'allowInventoryManagement' => true,
                            'character'                => $character,
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
