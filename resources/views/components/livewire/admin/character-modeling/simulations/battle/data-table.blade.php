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
                        
                        <x-data-tables.header-row>
                            Actions
                        </x-data-tables.header-row>
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($data as $result)
                            <tr>
                                <td>{{$result->id}}</td>
                                <td>{{$result->character->name}} {{$result->character->race->name}} - {{$result->character->class->name}}</td>
                                <td>{{count($result->battle_simmulation_data) - 1}}</td>
                                <td><a href="{{route('admin.character.modeling.battle-simmulation.results', ['characterSnapShot' => $result->id])}}" class="btn btn-primary btn-sm">View Results</a></td>
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="5"/>
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>
            </div>
        </div>
    </div>
</div>
