<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$units">
                    <x-data-tables.header>
                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('name')" 
                            header-text="Name" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('attack')" 
                            header-text="Attack" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="attack"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('defence')" 
                            header-text="Defence" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="defence"
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

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('required_population')" 
                            header-text="Required Population" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="required_population"
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
                        @forelse($units as $unit)
                            <tr>
                                <td>
                                    <a href="{{route('units.unit', [
                                        'gameUnit' => $unit->id
                                    ])}}">{{$unit->name}}</a>
                                </td>
                                <td>{{$unit->attack}}</td> 
                                <td>{{$unit->defence}}</td>
                                <td>{{$unit->wood_cost}}</td> 
                                <td>{{$unit->clay_cost}}</td> 
                                <td>{{$unit->stone_cost}}</td> 
                                <td>{{$unit->iron_cost}}</td> 
                                <td>{{$unit->required_population}}</td> 

                                @guest
                                @else
                                    @if (auth()->user()->hasRole('Admin'))
                                        <td>
                                            <a href="{{route('units.edit', [
                                                'gameUnit' => $unit->id
                                            ])}}" class="btn btn-primary">Edit</a>
                                        </td>
                                    @endif
                                @endguest
                            </tr>
                        @empty
                            @guest
                                <x-data-tables.no-results colspan="8"/>
                            @else
                                @if (auth()->user()->hasRole('Admin'))
                                    <x-data-tables.no-results colspan="9"/>
                                @else
                                    <x-data-tables.no-results colspan="8"/> 
                                @endif
                            @endguest
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
