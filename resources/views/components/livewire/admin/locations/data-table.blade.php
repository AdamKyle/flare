<div class="row justify-content-center">
    <div class="col-md-12">
        <x-core.cards.card css="tw-mt-5 tw-w-full lg:tw-w-3/4 tw-m-auto">
            <div class="row pb-2">
                <x-data-tables.per-page wire:model="perPage" />
                <x-data-tables.search wire:model="search" />
            </div>
            <x-data-tables.table :collection="$locations">
                <x-data-tables.header>
                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('name')"
                        header-text="Name"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="name"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('game_maps.name')"
                        header-text="Map"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="game_maps.name"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('x')"
                        header-text="X Coordinate"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="x"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('y')"
                        header-text="Y Coordinate"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="y"
                    />
                </x-data-tables.header>
                <x-data-tables.body>
                    @forelse($locations as $location)
                        <tr>
                            <td>
                                @guest
                                    <a href="{{route('info.page.location', [
                                            'location' => $location->id
                                        ])}}">{{$location->name}}</a>
                                @else
                                    @if (auth()->user()->hasRole('Admin'))
                                        <a href="{{route('locations.location', [
                                            'location' => $location->id
                                        ])}}">{{$location->name}}</a>
                                    @else
                                        @if ($previousUrlIsInfo = strpos(url()->previous(), 'information') !== false)
                                            <a href="{{route('info.page.location', [
                                                'location' => $location->id
                                            ])}}">{{$location->name}}</a>
                                        @else
                                            <a href="{{route('game.locations.location', [
                                                'location' => $location->id
                                            ])}}">{{$location->name}}</a>
                                        @endif
                                    @endif
                                @endguest

                            </td>
                            <td>{{$location->map->name}}</td>
                            <td>{{$location->x}}</td>
                            <td>{{$location->y}}</td>
                        </tr>
                    @empty
                        <x-data-tables.no-results colspan="4" />
                    @endforelse
                </x-data-tables.body>
            </x-data-tables.table>
        </x-core.cards.card>>
    </div>
</div>
