<div class="mt-3">
    <x-tabs.pill-tabs-container>
        @foreach ($character->inventorySets as $index => $inventorySet)
            @php
                $active = ($index === 0 ? true : false);
                $icon = '';
                $iconClass = '';
                $navLinkClass = '';

                if (!$inventorySet->can_be_equipped) {
                    $icon = 'fas fa-exclamation-triangle';
                    $iconClass = 'inventory-set-error';
                    $navLinkClass = 'nav-link-warn';
                }

                if ($inventorySet->is_equipped) {
                    $icon = 'ra ra-knight-helmet';
                    $iconClass = 'inventory-set-equipped';
                    $navLinkClass = 'nav-link-success';
                }
            @endphp
            <x-tabs.tab
                tab="set-{{$index + 1}}"
                title="Set {{$index + 1}}"
                selected="{{$active ? 'true' : 'false'}}"
                active="{{$active ? 'true' : 'false'}}"
                icon="{{$icon}}"
                navLinkClass="{{$navLinkClass}}"
                iconClass="{{$iconClass}}"
            />
        @endforeach

    </x-tabs.pill-tabs-container>
    <x-tabs.tab-content>
        @php
            $indexOfActive = $character->inventorySets->search(function($set) {
                return $set->is_equipped;
            });
        @endphp
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
                            2 damage or 1 of each), 2 artifacts and 2 rings. Sets can be incomplete and still be equipped.
                        </p>
                        <p>
                            Remember, you can still use sets as a stash tab, which seem to be what you are doing here.
                            Gear in sets do not count towards your inventory max and can contain as any items as you please.
                        </p>
                    </div>
                    <hr />
                @endif

                @if ($indexOfActive !== false && $inventorySet->can_be_equipped)
                    @if ($indexOfActive !== $index && $inventorySet->slots->isNotEmpty())
                        <div class="alert alert-warning mb-3">
                            Equipping this set will replace: <strong>Set {{$indexOfActive + 1}}</strong>.
                        </div>
                    @endif
                @endif

                @livewire('character.inventory-sets.data-table', [
                    'character'    => $character,
                    'inventorySet' => $inventorySet,
                ])
            </x-tabs.tab-content-section>
        @endforeach
    </x-tabs.tab-content>
</div>
