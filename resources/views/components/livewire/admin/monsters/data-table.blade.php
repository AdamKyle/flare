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
                            wire:click.prevent="sortBy('game_map_id')"
                            header-text="Plane"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="game_map_id"
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
                                        <a href="{{route('info.page.monster', [
                                                'monster' => $monster->id
                                            ])}}">{{$monster->name}}
                                        </a>
                                    @else
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('monsters.monster', [
                                                'monster' => $monster->id
                                            ])}}">{{$monster->name}}</a>
                                        @else
                                            @if ($previousUrlIsInfo = strpos(url()->previous(), 'information') !== false)
                                                <a href="{{route('info.page.monster', [
                                                    'monster' => $monster->id
                                                ])}}">{{$monster->name}}</a>
                                            @else
                                                <a href="{{route('game.monsters.monster', [
                                                    'monster' => $monster->id
                                                ])}}">{{$monster->name}}</a>
                                            @endif
                                        @endif
                                    @endguest
                                </td>
                                <td>{{$monster->gameMap->name}}</td>
                                <td>{{$monster->max_level}}</td>
                                <td>{{$monster->damage_stat}}</td>
                                <td>{{$monster->health_range}}</td>
                                <td>{{$monster->attack_range}}</td>
                                <td>{{$monster->xp}}</td>
                                <td>{{number_format($monster->gold)}}</td>
                                @guest
                                @else
                                <td>
                                    @if (auth()->user()->hasRole('Admin'))
                                        @if (!\Cache::has('processing-battle-' . $monster->id))
                                            <a href="{{route('monster.edit', [
                                                    'monster' => $monster->id,
                                            ])}}" class="btn btn-primary mt-2">Edit</a>
                                        @endif

                                        @if (!\Cache::has('processing-battle-' . $monster->id) && $testCharacters->isNotEmpty())
                                            <button type="button" class="btn btn-primary mt-2" data-toggle="modal" data-target="#monster-test-{{$monster->id}}">
                                                Test
                                            </button>
                                            @include('admin.character-modeling.partials.modals.monster-test-modal', [
                                                'monster' => $monster,
                                                'users'   => $testCharacters,
                                            ])
                                        @endif

                                        @foreach ($testCharacters as $user)
                                            @if ($user->character->snapShots()->where('battle_simmulation_data->monster_id', $monster->id)->get()->isNotEmpty())
                                                <a href="{{route('admin.character.modeling.monster-data', ['monster' => $monster])}}" class="btn btn-success mt-2">View Data</a>
                                                @break;
                                            @endif
                                        @endforeach
                                    @endif
                                    @if(auth()->user()->hasRole('Admin'))
                                        @if (!$published && !\Cache::has('processing-battle-' . $monster->id))
                                            <x-forms.button-with-form
                                                form-route="{{route('monster.publish', ['monster' => $monster])}}"
                                                form-id="publish-monster-{{$monster->id}}"
                                                button-title="Publish Monster"
                                                form-method="POST"
                                                class="btn btn-success mt-2"
                                            />
                                        @endif
                                    @endif

                                    @if (\Cache::has('processing-battle-' . $monster->id))
                                      Testing underway. We will email you when done.
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
