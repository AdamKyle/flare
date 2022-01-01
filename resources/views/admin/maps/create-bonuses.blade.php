@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Map Bonuses</h4>
                        <form class="mt-4" action="{{route('add.map.bonuses', ['gameMap' => $gameMap])}}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="name">XP Bonus</label>
                                <input type="number" step="0.0001" class="form-control" id="xp_bonus" aria-describedby="xp_bonus" name="xp_bonus" value="{{$gameMap->xp_bonus}}">
                            </div>

                            <div class="form-group">
                                <label for="name">Skill Training Bonus</label>
                                <input type="number" step="0.0001" class="form-control" id="skill_training_bonus" aria-describedby="skill_training_bonus" name="skill_training_bonus" value="{{$gameMap->skill_training_bonus}}">
                            </div>

                            <div class="form-group">
                                <label for="name">Drop Chance Bonus</label>
                                <input type="number" step="0.0001" class="form-control" id="drop_chance_bonus" aria-describedby="drop_chance_bonus" name="drop_chance_bonus" value="{{$gameMap->drop_chance_bonus}}">
                            </div>

                            <div class="form-group">
                                <label for="name">Enemy Stat increase by (%)</label>
                                <input type="number" step="0.0001" class="form-control" id="enemy_stat_bonus" aria-describedby="enemy_stat_bonus" name="enemy_stat_bonus" value="{{$gameMap->enemy_stat_bonus}}">
                            </div>

                            <div class="form-group">
                                <label for="name">Character Damage Deduction by (%)</label>
                                <input type="number" step="0.0001" class="form-control" id="character_attack_reduction" aria-describedby="character_attack_reduction" name="character_attack_reduction" value="{{$gameMap->character_attack_reduction}}">
                            </div>

                            <div class="form-group">
                                <label for="required_location_id">Requires player to be at location</label>
                                <select class="form-control" id="required_location_id" name="required_location_id">
                                    <option value="">Please select</option>
                                    @foreach($locations as $location)
                                        <option value="{{$location->id}}" {{$gameMap->required_location_id === $location->id ? 'selected' : ''}}>{{$location->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
