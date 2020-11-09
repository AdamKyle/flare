<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                @empty ($selected)
                @else
                    <div class="float-right pb-2">
                        <x-forms.button-with-form
                            form-route="{{route('game.shop.buy.bulk')}}"
                            form-id="{{'shop-buy-form-item-in-bulk'}}"
                            button-title="Buy All"
                            class="btn btn-primary btn-sm"
                        >
                            @forelse( $selected as $item)
                                <input type="hidden" name="items[]" value="{{$item}}" />
                            @empty
                                <input type="hidden" name="items[]" value="" />
                            @endforelse
                            
                        </x-forms.button-with-form>
                    </div>
                @endempty
                <x-data-tables.table :collection="$items">
                    <x-data-tables.header>
                        <x-data-tables.header-row>
                            <input type="checkbox" wire:model="pageSelected"/>
                        </x-data-tables.header-row>

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('name')" 
                            header-text="Name" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('type')" 
                            header-text="Type" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="type"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('base_damage')" 
                            header-text="Base Damage" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="base_damage"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('base_ac')" 
                            header-text="Base AC" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="base_ac"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('base_ac')" 
                            header-text="Base AC" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="base_ac"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('cost')" 
                            header-text="Cost" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="cost"
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
                                            <span>You have selected <strong>{{$items->count()}}</strong> items of <strong>{{$items->total()}}</strong>. Would you like to select all?</span>
                                            <button class="btn btn-link" wire:click="selectAll">Select all</button>
                                        </div>
                                    @else
                                        <span>You are currently selecting all <strong>{{$items->total()}}</strong> items.</span>
                                    @endunless
                                </td>
                            </tr>
                        @endif
                        @forelse($items as $item)
                            <tr wire:key="items-table-{{$item->id}}">
                                <td>
                                    <input type="checkbox" wire:model="selected" value="{{$item->id}}"/>
                                </td>
                                <td><a href="{{route('items.item', [
                                    'item' => $item->id
                                ])}}"><x-item-display-color :item="$item" /></a></td>
                                <td>{{$item->type}}</td>
                                <td>{{is_null($item->base_damage) ? 'N/A' : $item->base_damage}}</td>
                                <td>{{is_null($item->base_ac) ? 'N/A' : $item->base_ac}}</td>
                                <td>{{is_null($item->base_healing) ? 'N/A' : $item->base_healing}}</td>
                                <td>{{is_null($item->cost) ? 'N/A' : $item->cost}}</td>
                                <td>
                                    @if(auth()->user()->hasRole('Admin'))

                                        <a href="{{route('items.edit', [
                                            'item' => $item->id
                                        ])}}" class="btn btn-sm btn-primary">
                                            Edit
                                        </a> 

                                        <x-forms.button-with-form
                                            form-route="{{route('items.delete', [
                                                'item' => $item->id
                                            ])}}"
                                            form-id="{{'delete-item-'.$item->id}}"
                                            button-title="Delete"
                                            class="btn btn-danger btn-sm"
                                        /> 
                                    @else
                                        <x-forms.button-with-form
                                            form-route="{{route('game.shop.buy.item')}}"
                                            form-id="{{'shop-buy-form-item-'.$item->id}}"
                                            button-title="Buy"
                                            class="btn btn-primary btn-sm"
                                        >
                                            <input type="hidden" name="item_id" value={{$item->id}} />
                                        </x-forms.button-with-form> 
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="8"/>
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
