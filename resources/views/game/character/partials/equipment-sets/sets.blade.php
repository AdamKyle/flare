<div class="mt-3">
    <x-tabs.pill-tabs-container>
        @foreach ($character->inventorySets as $index => $inventorySet)
            @php
                $numberAsWord = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                $active = ($index === 0 ? true : false);
            @endphp
            <x-tabs.tab
                tab="set-{{$index + 1}}"
                title="Set {{$numberAsWord->format($index + 1)}}"
                selected="{{$active ? 'true' : 'false'}}"
                active="{{$active ? 'true' : 'false'}}"
                icon="{{!$inventorySet->can_equip ? 'fas fa-exclamation-triangle' : ''}}"
            />
        @endforeach

    </x-tabs.pill-tabs-container>
    <x-tabs.tab-content>
        @foreach($character->inventorySets as $index => $inventorySet)
            @php
                $numberAsWord = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
                $active = ($index === 0 ? true : false);
            @endphp

            <x-tabs.tab-content-section tab="set-{{$index + 1}}" active="{{$active ? 'true' : 'false'}}">
                @if (!$inventorySet->can_equip)
                    <div class="alert alert-warning mt-2 mb-3">
                        <p>
                            This set cannot be equipped due to the items in it.
                            Remember a set contains: 2 weapons (or 1 weapon and a shield) one of each armour
                            piece (excluding shield if you are dual wielding), 2 rings, 2 spells and 2 artifacts.
                        </p>
                        <p>Sets may be incomplete, in that case we will just replace the appropriate gear.</p>
                        <p>You may also treat sets as a stash tab, which seems to be what you are doing here - they just cant be equipped automatically.</p>
                    </div>
                @else
                    <button class="btn btn-primary btn-sm">Equip Set</button>
                @endif
                <hr />

                {{$numberAsWord->format($index + 1)}}
            </x-tabs.tab-content-section>
        @endforeach
    </x-tabs.tab-content>
</div>
