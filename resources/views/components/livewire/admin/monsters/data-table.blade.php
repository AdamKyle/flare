<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$monsters">
                    <x-data-tables.header>
                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('name')" 
                            header-text="Name" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('max_level')" 
                            header-text="Max Level" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="max_level"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('damage_stat')" 
                            header-text="Damage Stat" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="damage_stat"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('health_range')" 
                            header-text="Health Range" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="health_range"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('attach_range')" 
                            header-text="Attack Range" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="attach_range"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('xp')" 
                            header-text="XP" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="xp"
                        />

                        <x-data-tables.header-row 
                            wire:click.prevent="sortBy('gold')" 
                            header-text="Gold" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="gold"
                        />
                        @guest
                        @else
                            @if (auth()->user()->hasRole('Admin'))
                                <x-data-tables.header-row>
                                    Actions
                                </x-data-tables.header-row>
                            @endif
                        @endguest
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($monsters as $monster)
                            <tr>
                                <td>
                                    @guest
                                        {{$monster->name}}
                                    @else
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('monsters.monster', [
                                                'monster' => $monster->id
                                            ])}}">{{$monster->name}}</a>
                                        @else
                                            <a href="{{route('game.monsters.monster', [
                                                'monster' => $monster->id
                                            ])}}">{{$monster->name}}</a>
                                        @endif
                                    @endguest
                                </td>
                                <td>{{$monster->max_level}}</td>
                                <td>{{$monster->damage_stat}}</td>
                                <td>{{$monster->health_range}}</td>
                                <td>{{$monster->attack_range}}</td>
                                <td>{{$monster->xp}}</td>
                                <td>{{$monster->gold}}</td>
                                @guest
                                @else
                                    @if (auth()->user()->hasRole('Admin'))
                                        <td>
                                            <a href="{{route('monster.edit', [
                                                'monster' => $monster->id,
                                            ])}}" class="btn btn-primary mt-2">Edit</a>
                                        </td>
                                    @endif
                                @endguest
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="7" />
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
