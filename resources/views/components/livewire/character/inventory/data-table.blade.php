<div class="row justify-content-center">
    <div class="col-md-12">
        @if (!$hasEmptyInventorySets && $allowUnequipAll)
            <div class="alert alert-warning mt-2 mb-3">
                You do not have any empty sets to save this set into.
            </div>
        @endif
        @if ($inventorySetEquipped)
            <div class="alert alert-info">
                You currently have a set equipped. Equipping any other item, or set will replace this set completely.
                You <strong>cannot</strong> mix and match sets or sets with non set items. <strong>It's one or the other.</strong>
            </div>
        @endif
        <div class="alert alert-warning mt-2 mb-3 hide" wire:loading.class.remove="hide" wire:target="destroyAllItems">
            <i class="fas fa-spinner fa-spin"></i> Processing request. <strong>Do not</strong> refresh or leave this page. The page will refresh when done.
            You can check the game tab (if you have that open in a separate tab) to see each item be disenchanted. It is advised you do not do any additional
            actions while this is processing as it can slow the game down.
        </div>
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
                        >
                            <input type="hidden" name="is_set_equipped" value="{{$inventorySetEquipped}}">
                        </x-forms.button-with-form>

                        @if (!$inventorySetEquipped && $hasEmptyInventorySets)
                            <a href="#" class="btn btn-primary btn-sm ml-2" data-toggle="modal" data-target="#character-{{$character->id}}">Save as set</a>
                            @include('game.character.partials.equipment.modals.save-as-set-modal', [
                                'character' => $character
                            ])
                        @endif
                    @endif


                    @if ($allowMassDestroy)
                        <button type="button" wire:click="destroyAllItems" class="btn btn-danger btn-sm ml-2"> Destroy All</button>
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
                    @if ($batchSell || $onlyUsable)
                        <x-data-tables.header-row>
                            @if (!$onlyUsable)
                                <input type="checkbox" wire:model="pageSelected" id="select-all" />
                            @endif
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

                    @if ($allowUnequipAll)
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('position')"
                            header-text="Position"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="position"
                        />
                    @endif

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

                    @if (empty($pageSelected) && !empty($selected) && !$onlyUsable)
                        <div class="alert alert-info">
                            Selecting all items, will <strong>not</strong> destroy currently equipped or quest items. Everything else <strong>will be</strong> destroyed.
                        </div>
                    @endif

                    @if ($onlyUsable)
                        <button
                            class="btn btn-primary btn-sm mb-3"
                            data-toggle="modal"
                            data-target="#use-multiple-items"
                            {{count($selected) > 10 || count($selected) === 0 ? 'disabled' : ''}}
                        >Use Selected Items</button>

                        @include('game.character.partials.equipment.modals.use-multiple-modal', [
                            'selected'  => $selected,
                            'character' => $character,
                        ])
                    @endif

                    @forelse($slots as $slot)
                        <tr wire:key="slots-table-{{$slot->id}}">
                            @if ($batchSell || $onlyUsable)
                                @if ($onlyUsable && !$slot->item->damages_kingdoms)
                                    <td>
                                        <input type="checkbox" wire:model="selected" value="{{$slot->id}}"/>
                                    </td>
                                @elseif ($onlyUsable && $slot->item->damages_kingdoms)
                                    <td></td>
                                @else
                                    <td>
                                        <input type="checkbox" wire:model="selected" value="{{$slot->id}}"/>
                                    </td>
                                @endif
                            @endif
                            <td><a href="{{route('game.items.item', [
                                    'item' => $slot->item->id
                                ])}}"><x-item-display-color :item="$slot->item" /></a></td>
                            <td>{{$slot->item->type}}</td>
                            <td>{{is_null($slot->item->base_damage) ? 'N/A' : $slot->item->base_damage}}</td>
                            <td>{{is_null($slot->item->base_ac) ? 'N/A' : $slot->item->base_ac}}</td>
                            <td>{{is_null($slot->item->base_healing) ? 'N/A' : $slot->item->base_healing}}</td>
                            @if ($allowUnequipAll)
                                <td>{{ucfirst(implode(' ', explode('-', $slot->position)))}}</td>
                            @endif
                            @if (!$marketBoard && $batchSell)
                                <td>{{is_null($slot->item->cost) ? 'N/A' : number_format($slot->item->cost)}}</td>
                            @endif
                            <td>
                                @if ($allowInventoryManagement && $slot->item->type !== 'quest' && !$slot->item->damages_kingdoms)
                                    @include('game.character.partials.equipment.drop-downs.equip-dropdown', [
                                        'slot' => $slot,
                                        'character' => $character,
                                        'inventorySetEquipped' => $inventorySetEquipped,
                                    ])

                                    @include('game.character.partials.equipment.modals.destroy-modal', [
                                        'slot'      => $slot,
                                        'character' => $character
                                    ])

                                    @include('game.character.partials.equipment.modals.move-to-set-modal', [
                                        'slot'      => $slot,
                                        'character' => $character
                                    ])
                                @else
                                    @if ($slot->item->damages_kingdoms)
                                        Damages Kingdoms.
                                    @elseif ($slot->item->type !== 'quest' && !$onlyUsable )
                                        @include('game.character.partials.equipment.drop-downs.sell-item', [
                                            'slot' => $slot,
                                            'character' => $character
                                        ])
                                    @elseif ($onlyUsable)
                                        <button class="btn btn-primary" data-toggle="modal" data-target="#slot-use-{{$slot->id}}">Use</button>
                                        @include('game.character.partials.equipment.modals.use-modal', [
                                            'slot'      => $slot,
                                            'character' => $character,
                                            'skills'    => $slot->affects_skills,
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
