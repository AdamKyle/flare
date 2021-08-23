<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$gameClasses">
                    <x-data-tables.header>
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('name')"
                            header-text="Name"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="name"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('damage_stat')"
                            header-text="Damage Stat"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="damage_stat"
                        />
                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('to_hit_stat')"
                            header-text="To Hit Stat"
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="to_hit_stat"
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
                        @forelse($gameClasses as $class)
                            <tr>
                                <td>
                                    @if (!is_null(auth()->user()))
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('classes.class', [
                                                'class' => $class->id
                                            ])}}">
                                                {{$class->name}}
                                            </a>
                                        @else
                                            <a href="{{route('info.page.class', [
                                                'class' => $class
                                            ])}}">
                                                {{$class->name}}
                                            </a>
                                        @endif
                                    @else
                                        <a href="{{route('info.page.class', [
                                            'class' => $class
                                        ])}}">
                                            {{$class->name}}
                                        </a>
                                    @endif
                                </td>
                                <td>{{$class->damage_stat}}</td>
                                <td>{{$class->to_hit_stat}}</td>
                                <td>{{$class->str_mod}} pts. </td>
                                <td>{{$class->dur_mod}} pts. </td>
                                <td>{{$class->dex_mod}} pts. </td>
                                <td>{{$class->chr_mod}} pts. </td>
                                <td>{{$class->int_mod}} pts. </td>
                                <td>{{$class->agi_mod}} pts. </td>
                                <td>{{$class->focus_mod}} pts. </td>
                                @guest
                                @else
                                    @if (auth()->user()->hasRole('Admin'))
                                        <td>
                                            @if (!\Cache::has('updating-characters') && !\Cache::has('updating-test-characters'))
                                                <a href="{{route('classes.edit', [
                                                    'class' => $class->id,
                                                ])}}" class="btn btn-primary mt-2 btn-sm">Edit</a>
                                            @else
                                                Currently updating all characters. You will be emailed when this is finished.
                                            @endif
                                        </td>
                                    @endif
                                @endguest
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="6" />
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
