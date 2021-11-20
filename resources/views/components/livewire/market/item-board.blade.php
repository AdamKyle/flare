<div class="row justify-content-center">
    <div class="col-md-12">
        <x-core.cards.card css="tw-mt-5 tw-w-full lg:tw-w-3/4 tw-m-auto">
            <div class="row pb-2 ml-2 text-muted">
                This table may not reflect the marketboard at all times as it is not live updated.
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
                            <td><x-item-display-color :item="$item->item" /></td>
                            <td>{{$item->item->type}}</td>
                            <td>{{$item->character->name}}</td>
                            <td>{{$item->listed_price}}</td>
                        </tr>
                    @empty
                        <x-data-tables.no-results colspan="4" />
                    @endforelse
                </x-data-tables.body>
            </x-data-tables.table>
        </x-core.cards.card>
    </div>
</div>
