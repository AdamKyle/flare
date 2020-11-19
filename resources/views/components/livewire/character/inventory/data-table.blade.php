<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage">
                        @if ($batchSell)
                            <x-forms.button-with-form
                                form-route="{{route('game.shop.sell.all')}}"
                                form-id='shop-sell-all'
                                button-title="Sell All"
                                class="btn btn-primary btn-sm ml-2"
                            />
                        @endif

                        @if ($allowUnequipAll)
                            <x-forms.button-with-form
                                form-route="{{ route('game.unequip.all') }}"
                                form-id='unequip-all'
                                button-title="Unequip All"
                                class="btn btn-danger btn-sm ml-2"
                            />
                        @endif
                    </x-data-tables.per-page>
                    <x-data-tables.search wire:model="search" />
                </div>
                @if ($batchSell)
                    @empty ($selected)
                    @else
                        <div class="float-right pb-2">
                            <x-forms.button-with-form
                                form-route="{{route('game.shop.sell.bulk')}}"
                                form-id="{{'shop-sell-form-item-in-bulk'}}"
                                button-title="Sell All Selected"
                                class="btn btn-primary btn-sm"
                            >
                                @forelse( $selected as $item)
                                    <input type="hidden" name="slots[]" value="{{$item}}" />
                                @empty
                                    <input type="hidden" name="slots[]" value="" />
                                @endforelse
                                
                            </x-forms.button-with-form>
                        </div>
                    @endempty
                @endif

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

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('items.cost')" 
                            header-text="Cost" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="items.cost"
                        />

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
                        
                        @forelse($slots as $slot)
                            <tr wire:key="slots-table-{{$slot->id}}">
                                @if ($batchSell)
                                    <td>
                                        <input type="checkbox" wire:model="selected" value="{{$slot->id}}"/>
                                    </td>
                                @endif
                                <td><a href="{{route('items.item', [
                                    'item' => $slot->item->id
                                ])}}"><x-item-display-color :item="$slot->item" /></a></td>
                                <td>{{$slot->item->type}}</td>
                                <td>{{is_null($slot->item->base_damage) ? 'N/A' : $slot->item->base_damage}}</td>
                                <td>{{is_null($slot->item->base_ac) ? 'N/A' : $slot->item->base_ac}}</td>
                                <td>{{is_null($slot->item->base_healing) ? 'N/A' : $slot->item->base_healing}}</td>
                                <td>{{is_null($slot->item->Cost) ? 'N/A' : $slot->item->cost}}</td>
                                <td>
                                    @if ($allowInventoryManagement && $slot->item->type !== 'quest')
                                        <div class="dropdown">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="actionsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            Actions
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="actionsButton">
                                            @if (!$slot->equipped)
                                                <form id="item-comparison-{{$slot->id}}" action="{{route('game.inventory.compare')}}" method="GET" style="display: none">
                                                    @csrf
                
                                                    <input type="hidden" name="slot_id" value={{$slot->id}} />
                
                                                    @if ($slot->item->crafting_type === 'armour')
                                                        <input type="hidden" name="item_to_equip_type" value={{$slot->item->type}} />
                                                    @endif
                                                </form>
                
                                                <a class="dropdown-item" href="{{route('game.inventory.compare')}}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('item-comparison-{{$slot->id}}').submit();">
                                                    {{ __('Equip') }}
                
                                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#slot-{{$slot->id}}">Destroy</a>
                                            @else
                                                <form id="item-unequip-{{$slot->id}}" action="{{route('game.inventory.unequip')}}" method="POST" style="display: none">
                                                    @csrf
                
                                                    <input type="hidden" name="item_to_remove" value={{$slot->id}} />
                                                </form>
                                                <a class="dropdown-item" href="{{route('game.inventory.unequip')}}"
                                                    onclick="event.preventDefault();
                                                                document.getElementById('item-unequip-{{$slot->id}}').submit();">
                                                    {{ __('Unequip') }}</a>
                                            @endif
                
                                            </div>
                                        </div>
                
                                        @include('game.core.partials.destroy-modal', ['slot' => $slot])
                                    @else
                                        @if ($slot->item->type !== 'quest')
                                            <a class="btn btn-primary" href="{{route('game.shop.sell.item')}}"
                                                            onclick="event.preventDefault();
                                                            document.getElementById('shop-sell-form-slot-{{$slot->id}}').submit();">
                                                {{ __('Sell') }}
                                            </a>

                                            <form id="shop-sell-form-slot-{{$slot->id}}" action="{{route('game.shop.sell.item')}}" method="POST" style="display: none;">
                                                @csrf

                                                <input type="hidden" name="slot_id" value={{$slot->id}} />
                                            </form>
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
                
            </div>
        </div>
    </div>
</div>
