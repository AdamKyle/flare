<div class="row justify-content-center">
    <div class="col-md-12">
        <x-core.cards.card css="tw-mt-5 tw-w-full lg:tw-w-3/4 tw-m-auto">
            <div class="row pb-2">
                <x-data-tables.per-page wire:model="perPage">
                </x-data-tables.per-page>
                <x-data-tables.search wire:model="search" />
            </div>

            <x-data-tables.table :collection="$items">
                <x-data-tables.header>
                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('name')"
                        header-text="Name"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="name"
                    />
                    <x-data-tables.header-row
                      wire:click.prevent="sortBy('description')"
                      header-text="Description"
                      sort-by="{{$sortBy}}"
                      sort-field="{{$sortField}}"
                      field="description"
                    />
                </x-data-tables.header>
                <x-data-tables.body>
                    @forelse($items as $item)
                        <tr wire:key="items-table-{{$item->id}}">
                            <td>
                                <a href="{{route('info.page.item', [
                                    'item' => $item->id
                                ])}}">
                                    <x-item-display-color :item="$item" />
                                </a>
                            </td>
                            <td>
                                {{$item->description}}
                            </td>
                        </tr>
                    @empty
                        <x-data-tables.no-results colspan="1"/>
                    @endforelse
                </x-data-tables.body>
            </x-data-tables.table>
        </x-core.cards.card>>
    </div>
</div>
