<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <x-data-tables.table :collection="$data">
                    <x-data-tables.header>
                        <x-data-tables.header-row>
                            Snap Shot ID
                        </x-data-tables.header-row>

                        <x-data-tables.header-row>
                            Character Name
                        </x-data-tables.header-row>

                        <x-data-tables.header-row>
                            Total Fights
                        </x-data-tables.header-row>

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('did_die')" 
                            header-text="Died?" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="did_die"
                        />

                        <x-data-tables.header-row
                            wire:click.prevent="sortBy('failed_to_finish')" 
                            header-text="Took too long?" 
                            sort-by="{{$sortBy}}"
                            sort-field="{{$sortField}}"
                            field="failed_to_finish"
                        />
                        
                        <x-data-tables.header-row>
                            Actions
                        </x-data-tables.header-row>
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($data as $result)
                            @php
                            $count = count($result->battle_simmulation_data) - 1;

                            if ($count === 0) {
                                $count = 1;
                            }

                            @endphp
                            <tr>
                                <td>{{$result->id}}</td>
                                <td>{{$result->character->name}} {{$result->character->race->name}} - {{$result->character->class->name}}</td>
                                <td>{{$count}}</td>
                                <td>{{$result->did_die ? 'Yes' : 'No'}}</td>
                                <td>{{$result->failed_to_finish ? 'Yes' : 'No'}}</td>
                                <td><a href="{{route('admin.character.modeling.battle-simmulation.results', ['characterSnapShot' => $result->id])}}" class="btn btn-primary btn-sm">View Results</a></td>
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="6"/>
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
