<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$races">
                    <x-data-tables.header>
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('name')"
                            header-text="Name"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('str_mod')"
                            header-text="Strength Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="str_mod"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('dur_mod')"
                            header-text="Durabillity Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="dur_mod"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('dex_mod')"
                            header-text="Dexterity Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="dex_mod"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('chr_mod')"
                            header-text="Charisma Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="chr_mod"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('int_mod')"
                            header-text="Intelligence Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="int_mod"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('agi_mod')"
                            header-text="Agility Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="agi_mod"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('focus_mod')"
                            header-text="Focus Modifier"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="focus_mod"
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
                        @forelse($races as $race)
                            <tr>
                                <td>
                                    @if (!is_null(auth()->user()))
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('races.race', [
                                                'race' => $race
                                            ])}}">
                                                {{$race->name}}
                                            </a>
                                        @else
                                            <a href="{{route('info.page.race', [
                                                'race' => $race
                                            ])}}">
                                                {{$race->name}}
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{route('info.page.race', [
                                            'race' => $race
                                        ])}}">
                                            {{$race->name}}
                                        </a>
                                    @endif
                                </td>
                                <td>{{$race->str_mod}} pts.</td>
                                <td>{{$race->dur_mod}} pts.</td>
                                <td>{{$race->dex_mod}} pts.</td>
                                <td>{{$race->chr_mod}} pts.</td>
                                <td>{{$race->int_mod}} pts.</td>
                                <td>{{$race->agi_mod}} pts. </td>
                                <td>{{$race->focus_mod}} pts. </td>
                                @guest
                                @else
                                    @if (auth()->user()->hasRole('Admin'))
                                        <td>
                                            @if (!\Cache::has('updating-characters') && !\Cache::has('updating-test-characters'))
                                                <a href="{{route('races.edit', [
                                                    'race' => $race->id,
                                                ])}}" class="btn btn-primary mt-2 btn-sm">Edit</a>
                                            @else
                                                Currently updating all characters. You will be emailed when this is finished.
                                            @endif
                                        </td>
                                    @endif
                                @endguest
                            </tr>
                        @empty
                            @guest
                                <x-data-tables.no-results colspan="6" />
                            @else
                                @if (auth()->user()->hasRole('Admin'))
                                    <x-data-tables.no-results colspan="7" />
                                @else
                                    <x-data-tables.no-results colspan="6" />
                                @endif
                            @endguest
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
