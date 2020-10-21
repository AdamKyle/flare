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
                        
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @foreach($gameClasses as $class)
                            <tr>
                                <td>
                                    @if (!is_null(auth()->user()))
                                        @if (auth()->user()->hasRole('Admin'))
                                            <a href="{{route('classes.class', [
                                                'class' => $class->id
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
                                <td>{{$class->str_mod}} pts. </td>
                                <td>{{$class->dur_mod}} pts. </td>
                                <td>{{$class->dex_mod}} pts. </td>
                                <td>{{$class->chr_mod}} pts. </td>
                                <td>{{$class->int_mod}} pts. </td>
                            </tr>
                        @endforeach
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
