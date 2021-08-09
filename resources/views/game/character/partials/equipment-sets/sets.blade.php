<div class="mt-3">
    <x-tabs.pill-tabs-container>
        @foreach ($character->inventorySets as $index => $inventorySet)
            @php
                $active = ($index === 0 ? true : false);
            @endphp
            <x-tabs.tab
                tab="set-{{$index + 1}}"
                title="Set {{$index + 1}}"
                selected="{{$active ? 'true' : 'false'}}"
                active="{{$active ? 'true' : 'false'}}"
                icon="{{!$inventorySet->can_be_equipped ? 'fas fa-exclamation-triangle' : ''}}"
            />
        @endforeach

    </x-tabs.pill-tabs-container>
    <x-tabs.tab-content>
        @foreach($character->inventorySets as $index => $inventorySet)
            @php
                $active = ($index === 0 ? true : false);
            @endphp

            <x-tabs.tab-content-section tab="set-{{$index + 1}}" active="{{$active ? 'true' : 'false'}}">
                @if (!$inventorySet->can_be_equipped)
                    <div class="alert alert-warning mt-2 mb-3">
                        <p>
                            This set cannot be equipped due to the items in it.
                            Remember a set contains: 2 Weapons (or 1 Shield, 1 Weapon or 1 Bow), 1 of each piece of armour, 2 spells (either 2 healing or
                            2 damage or 1 of each) and 2 artifacts.
                        </p>
                        <p>
                            Remember, you can still use sets as a stash tab, which seem to be what you are doing here.
                            Gear in sets do not count towards your inventory max and can contain as any items as you please.
                        </p>
                    </div>
                @else
                    <button class="btn btn-primary btn-sm">Equip Set</button>
                @endif
                <hr />

                @livewire('character.inventory-sets.data-table', [
                    'character'    => $character,
                    'inventorySet' => $inventorySet,
                ])
            </x-tabs.tab-content-section>
        @endforeach
    </x-tabs.tab-content>
</div>
