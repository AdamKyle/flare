<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card item-board-card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$items">
                    <x-data-tables.header>
                        <x-data-tables.header-row>
                            Name
                        </x-data-tables.header-row>

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('items.type')" 
                            header-text="Type" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="items.type"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('characters.name')" 
                            header-text="Character" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="characters.name"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('listed_price')" 
                            header-text="Listed For" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="listed_price"
                        />
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($items as $item)
                            <tr wire:key="items-table-{{$item->id}}">
                                <td>{{$item->item->affix_name}}</td>
                                <td>{{$item->item->type}}</td>
                                <td>{{$item->character->name}}</td>
                                <td>{{$item->listed_price}}</td>
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="4" />
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
