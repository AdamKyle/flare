<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <x-data-tables.table :collection="$data">
                    <x-data-tables.header>
                        <x-data-tables.header-row>
                            Character Name
                        </x-data-tables.header-row>

                        <x-data-tables.header-row>
                            Died In Battle
                        </x-data-tables.header-row>

                        <x-data-tables.header-row>
                            Monster Name
                        </x-data-tables.header-row>

                        <x-data-tables.header-row>
                            Died In Battle
                        </x-data-tables.header-row>
                        
                        <x-data-tables.header-row>
                            Actions
                        </x-data-tables.header-row>
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($data as $result)
                            @if (!isset($result->battle_simmulation_data['character_name']))
                            @dd($result->battle_simmulation_data)
                            @endif
                            <tr>
                                <td>{{$result->battle_simmulation_data['character_name']}}</td>
                                <td>{{$result->battle_simmulation_data['character_dead'] ? 'Yes' : 'No'}}</td>
                                <td>{{$result->battle_simmulation_data['monster_name']}}</td>
                                <td>{{$result->battle_simmulation_data['monster_dead'] ? 'Yes' : 'No'}}</td>
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
