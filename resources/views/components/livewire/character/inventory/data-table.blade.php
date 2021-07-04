<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card additionalClasses="overflow-table">
            <div class="row pb-2">
                <x-data-tables.per-page wire:model="perPage">
                    @if ($batchSell)
                        <x-forms.button-with-form
                            form-route="{{route('game.shop.sell.all', ['character' => $character->id])}}"
                            form-id='shop-sell-all'
                            button-title="Sell All"
                            class="btn btn-primary btn-sm ml-2"
                        />
                    @endif

                    @if ($allowUnequipAll)
                        <x-forms.button-with-form
                            form-route="{{ route('game.unequip.all', ['character' => $character]) }}"
                            form-id='unequip-all'
                            button-title="Unequip All"
                            class="btn btn-danger btn-sm ml-2"
                        />
                    @endif


                    @if ($allowMassDestroy)
                        <button type="button" wire:click="destroyAllItems" class="btn btn-danger btn-sm ml-2">Destroy All</button>
                        <button type="button" wire:click="destroyAllItems('disenchant')" class="btn btn-primary btn-sm ml-2">Disenchant All</button>
                    @endif
                </x-data-tables.per-page>
                <x-data-tables.search wire:model="search" />
            </div>

            @include('components.livewire.character.inventory.partials.batch-sell', [
                'batchSell' => $batchSell,
                'selected'  => $selected,
                'character' => $character,
            ])

            <x-data-tables.table :collection="$slots">
                <x-data-tables.header>
                    @if ($batchSell)
                        <x-data-tables.header-row>
                            <input type="checkbox" wire:model="pageSelected" id="select-all" />
                        </x-data-tables.header-row>
                    @endif

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

                    @if (!$marketBoard && $batchSell)
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('items.cost')"
                            header-text="Cost"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="items.cost"
                        />
                    @endif

                    <x-data-tables.header-row>
                        Actions
                    </x-data-tables.header-row>
                </x-data-tables.header>

                <x-data-tables.body>
                    @if ($pageSelected)
                        <tr>
                            <td colspan="8">
                                @unless($selectAll)
                                    <div>
                                        <span>You have selected <strong>{{$slots->count()}}</strong> items of <strong>{{$slots->total()}}</strong>. Would you like to select all?</span>
                                        <button class="btn btn-link" wire:click="selectAll">Select all</button>
                                    </div>
                                @else
                                    <span>You are currently selecting all <strong>{{$slots->total()}}</strong> items.</span>
                                @endunless
                            </td>
                        </tr>
                    @endif

                    @if (empty($pageSelected) && !empty($selected))
                        <div class="alert alert-info">
                            Selecting all items, will <strong>not</strong> destroy currently equipped or quest items. Everything else <strong>will be</strong> destroyed.
                        </div>
                    @endif

                    @forelse($slots as $slot)
                        <tr wire:key="slots-table-{{$slot->id}}">
                            @if ($batchSell)
                                <td>
                                    <input type="checkbox" wire:model="selected" value="{{$slot->id}}"/>
                                </td>
                            @endif
                            <td><a href="{{route('game.items.item', [
                                    'item' => $slot->item->id
                                ])}}"><x-item-display-color :item="$slot->item" /></a></td>
                            <td>{{$slot->item->type}}</td>
                            <td>{{is_null($slot->item->base_damage) ? 'N/A' : $slot->item->base_damage}}</td>
                            <td>{{is_null($slot->item->base_ac) ? 'N/A' : $slot->item->base_ac}}</td>
                            <td>{{is_null($slot->item->base_healing) ? 'N/A' : $slot->item->base_healing}}</td>
                            @if (!$marketBoard && $batchSell)
                                <td>{{is_null($slot->item->cost) ? 'N/A' : number_format($slot->item->cost)}}</td>
                            @endif
                            <td>
                                @if ($allowInventoryManagement && $slot->item->type !== 'quest')
                                    @include('game.character.partials.equipment.drop-downs.equip-dropdown', [
                                        'slot' => $slot,
                                        'character' => $character
                                    ])

                                    @include('game.character.partials.equipment.modals.destroy-modal', [
                                        'slot'      => $slot,
                                        'character' => $character
                                    ])

                                    @include('game.character.partials.equipment.modals.use-modal', [
                                        'slot'      => $slot,
                                        'character' => $character
                                    ])
                                @elseif ($marketBoard)
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#slot-id-{{$slot->id}}">
                                        Sell On Market
                                    </button>

                                    @include('components.livewire.character.inventory.partials.market-sell-modal', [
                                        'slot' => $slot
                                    ])
                                @else
                                    @if ($slot->item->type !== 'quest')
                                        @include('game.character.partials.equipment.drop-downs.sell-item', [
                                            'slot' => $slot,
                                            'character' => $character
                                        ])
                                    @else
                                        N/A
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        @if ($batchSell)
                            <x-data-tables.no-results colspan="8" />
                        @else
                            <x-data-tables.no-results colspan="7" />
                        @endif
                    @endforelse
                </x-data-tables.body>
            </x-data-tables.table>
        </x-cards.card>
    </div>
</div>
