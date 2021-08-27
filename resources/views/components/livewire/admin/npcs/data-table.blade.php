<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$npcs">
                    <x-data-tables.header>
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('real_name')"
                            header-text="Name"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="real_name"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('game_map_id')"
                            header-text="Plane"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="game_map_id"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('moves_around_map')"
                            header-text="Moves Around Map"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="moves_around_map"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('must_be_at_same_location')"
                            header-text="Must be At Same Location"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="must_be_at_same_location"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('text_command_to_message')"
                            header-text="Text Command"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="text_command_to_message"
                        />

                        @auth
                            @if (auth()->user()->hasRole('Admin'))
                                <x-data-tables.header-row>
                                    Actions
                                </x-data-tables.header-row>
                            @endif
                        @endauth
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($npcs as $npc)
                            <tr>
                                <td>
                                    @guest
                                        <a href="{{route('information.npcs.npc', ['npc' => $npc->id])}}">{{$npc->real_name}}
                                        </a>
                                    @else
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('npcs.show', [
                                                'npc' => $npc->id
                                            ])}}">{{$npc->real_name}}</a>
                                        @else
                                            <a href="{{route('information.npcs.npc', ['npc' => $npc->id])}}">{{$npc->real_name}}</a>
                                        @endif
                                    @endguest
                                </td>
                                <td>{{$npc->gameMap->name}}</td>
                                <td>{{$npc->moves_around_the_map ? 'Yes' : 'No'}}</td>
                                <td>{{$npc->must_be_at_same_location ? 'Yes' : 'No'}}</td>
                                <td>{{$npc->text_command_to_message}} {{$npc->commands->first()->command}}</td>
                                @guest
                                @else
                                    <td>
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('npcs.edit', [
                                                    'npc' => $npc->id,
                                            ])}}" class="btn btn-primary mt-2">Edit</a>
                                        @endif
                                    </td>
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
