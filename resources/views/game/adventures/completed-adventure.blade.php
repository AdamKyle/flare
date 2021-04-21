@extends('layouts.app')

@section('content')
    <div class="justify-content-center">
        <x-core.page-title 
            title="{{$adventureLog->adventure->name}}"
            route="{{route('game.completed.adventures')}}"
            link="Back"
            color="success"
        ></x-core.page-title>

        <div class="card">
            <div class="card-body">
                <p>
                    {{$adventureLog->adventure->description}}
                </p>
                <hr />
                <dl>
                    <dt>Levels</dt>
                    <dd>{{$adventureLog->adventure->levels}}</dd>
                    <dt>Time Per Level (Minutes)</dt>
                    <dd>{{$adventureLog->adventure->time_per_level}}</dd>
                    <dt>Item Find Chance</dt>
                    <dd>{{$adventureLog->adventure->item_find_chance * 100}}%</dd>
                    <dt>Gold Rush Chance</dt>
                    <dd>{{$adventureLog->adventure->gold_rush_chance * 100}}%</dd>
                    <dt>Skill Bonus EXP</dt>
                    <dd>{{$adventureLog->adventure->skill_exp_bonus * 100}}%</dd>
                </dl>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Log Entry</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        @php $count = count($adventureLog->logs); @endphp
                        @foreach(array_reverse($adventureLog->logs) as $logName => $log)
                            <tr>
                                <td><a href={{route('game.completed.adventure.logs', [
                                    'adventureLog' => $adventureLog->id,
                                    'name'         => $logName
                                ])}}>{{'Log Entry ' . $count}}</a></td>
                            </tr>
                            @php $count--; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
