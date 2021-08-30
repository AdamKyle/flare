<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$maps">
                    <x-data-tables.header>
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('name')"
                            header-text="Name"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />
                        @guest
                        @elseif (auth()->user()->hasRole('Admin'))
                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('default')"
                                header-text="Default Starting Map"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="default"
                            />
                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('characters_using')"
                                header-text="Characters Using"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="characters_using"
                            />
                            <x-data-tables.header-row>
                                Actions
                            </x-data-tables.header-row>
                        @endguest
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($maps as $map)
                            <tr>
                                @guest
                                    <td class="text-center">
                                        <a href="{{route('info.page.map', ['map' => $map])}}">{{$map->name}}</a>
                                    </td>
                                @elseif (auth()->user()->hasRole('Admin'))
                                    <td>
                                        <a href="{{route('info.page.map', ['map' => $map])}}">{{$map->name}}</a>
                                    </td>
                                    <td>{{$map->default ? 'Yes' : 'No'}}</td>
                                    <td>{{$map->characters_using}}</td>

                                    @if (!$map->mapHasBonuses())
                                        <td><a href="{{route('map.bonuses', ['gameMap' => $map->id])}}" class="btn btn-small btn-primary">Add Bonuses</a></td>
                                    @else
                                        <td>
                                            <a href="{{route('map.bonuses', ['gameMap' => $map->id])}}" class="btn btn-small btn-primary">Edit Bonuses</a>
                                            <a href="{{route('view.map.bonuses', ['gameMap' => $map->id])}}" class="btn btn-small btn-success">View Bonuses</a>
                                        </td>
                                    @endif
                                @elseif (auth()->user())
                                    <td class="text-center">
                                        <a href="{{route('info.page.map', ['map' => $map])}}">{{$map->name}}</a>
                                    </td>
                                @endguest
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
