<div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <x-cards.card additionalClasses="overflow-table">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage">
                        @if ($inventorySet->can_be_equipped)
                            <x-forms.button-with-form
                                form-route="{{route('game.equip.set', ['character' => $character->id, 'inventorySet' => $inventorySet->id])}}"
                                form-id='equip.set'
                                button-title="Equip Set"
                                class="btn btn-primary btn-sm ml-2"
                            />
                        @endif
                    </x-data-tables.per-page>

                    <x-data-tables.search wire:model="search" />
                </div>

                <x-data-tables.table :collection="$slots">
                    <x-data-tables.header>
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('items.name')"
                            header-text="Name"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="items.name"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('items.type')"
                            header-text="Type"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="items.type"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('items.base_damage')"
                            header-text="Base Damage"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="items.base_damage"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('items.base_ac')"
                            header-text="Base AC"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="items.base_ac"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('items.base_healing')"
                            header-text="Base Healing"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="items.base_healing"
                        />

                        <x-data-tables.header-row>
                            Actions
                        </x-data-tables.header-row>
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($slots as $slot)
                            <tr wire:key="slots-table-{{$slot->id}}">
                                <td><a href="{{route('game.items.item', [
                                    'item' => $slot->item->id
                                ])}}"><x-item-display-color :item="$slot->item" /></a></td>
                                <td>{{$slot->item->type}}</td>
                                <td>{{is_null($slot->item->base_damage) ? 'N/A' : $slot->item->base_damage}}</td>
                                <td>{{is_null($slot->item->base_ac) ? 'N/A' : $slot->item->base_ac}}</td>
                                <td>{{is_null($slot->item->base_healing) ? 'N/A' : $slot->item->base_healing}}</td>
                                <td>
                                    @include('game.character.partials.equipment.drop-downs.set-dropdown', [
                                        'slot'         => $slot,
                                        'character'    => $character,
                                        'inventorySet' => $inventorySet,
                                    ])
                                </td>
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="5" />
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </x-cards.card>
        </div>
    </div>
</div>
