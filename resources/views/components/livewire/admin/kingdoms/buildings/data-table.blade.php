<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$buildings">
                    <x-data-tables.header>
                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('name')" 
                            header-text="Name" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('base_durability')" 
                            header-text="Base Durability" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="base_durability"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('base_defence')" 
                            header-text="Base Defence" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="base_defence"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('wood_cost')" 
                            header-text="Wood Cost" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="wood_cost"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('clay_cost')" 
                            header-text="Clay Cost" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="clay_cost"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('stone_cost')" 
                            header-text="Stone Cost" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="stone_cost"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('iron_cost')" 
                            header-text="Iron Cost" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="iron_cost"
                        />

                        @guest
                        @else
                            @if (auth()->user()->hasRole('Admin'))
                                <x-data-tables.header-row>
                                    Actions
                                </x-data-tables.header-row>
                            @endif
                        @endGuest
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($buildings as $building)
                            <tr>
                                <td>
                                    <a href="{{route('buildings.building', [
                                        'building' => $building->id
                                    ])}}">{{$building->name}}</a>
                                </td>
                                <td>{{$building->base_durability}}</td> 
                                <td>{{$building->base_defence}}</td>
                                <td>{{$building->wood_cost}}</td> 
                                <td>{{$building->clay_cost}}</td> 
                                <td>{{$building->stone_cost}}</td> 
                                <td>{{$building->iron_cost}}</td> 

                                @guest
                                @else
                                    @if (auth()->user()->hasRole('Admin'))
                                        <td>
                                            <a href="{{route('buildings.edit', [
                                                'building' => $building->id
                                            ])}}" class="btn btn-primary">Edit</a>
                                        </td>
                                    @endif
                                @endguest
                            </tr>
                        @empty
                            @guest
                                <x-data-tables.no-results colspan="7"/>
                            @else
                                @if (auth()->user()->hasRole('Admin'))
                                    <x-data-tables.no-results colspan="8"/>
                                @else
                                    <x-data-tables.no-results colspan="7"/> 
                                @endif
                            @endguest
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
