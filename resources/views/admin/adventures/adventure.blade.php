@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="container justify-content-center">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title float-left">{{$adventure->name}}</h4>

                <div class="clearfix">
                    @if(auth()->user()->hasRole('Admin'))
                        <div class="col-md-12 align-self-right">
                            <a href="{{route('adventures.list')}}" class="btn btn-primary float-right ml-2">Back</a>
                        </div>
                    @endif
                </div>
                
                <p>{{$adventure->description}}</p>
                <hr />
                <dl>
                    <dt>Levels</dt>
                    <dd>{{$adventure->levels}}</dd>
                    <dt>Time Per Level (Minutes)</dt>
                    <dd>{{$adventure->time_per_level}}</dd>
                    <dt>Item Find Chance</dt>
                    <dd>{{$adventure->item_find_chance * 100}}%</dd>
                    <dt>Gold Rush Chance</dt>
                    <dd>{{$adventure->gold_rush_chance * 100}}%</dd>
                    <dt>Skill Bonus EXP</dt>
                    <dd>{{$adventure->skill_exp_bonus * 100}}%</dd>
                </dl>
                <hr />
                @if (auth()->user()->hasRole('Admin'))
                    <a href="{{route('adventure.edit', [
                        'adventure' => $adventure->id,
                    ])}}" class="btn btn-primary mt-2">Edit Adventure</a>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Found At:</h4>
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Positon X</th>
                            <th>Positon Y</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adventure->locations as $location)
                        <tr>
                            <td>{{$location->name}}</td>
                            <td>{{$location->x}}</td>
                            <td>{{$location->y}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">With Monsters:</h4>
                <div class="alert alert-info">Monsters are selected at random for each adventure level.</div>
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adventure->monsters as $monster)
                        <tr>
                            <td>{{$monster->name}}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Rewards: {{$adventure->itemReward->name}}</h4>
                <em class="text-muted">All quest items are rewarded once for completing the adventure</em>
                <div class="mt-2">
                    @if (!is_null($adventure->itemReward))
                        @include('game.items.partials.item-details', ['item' => $adventure->itemReward])
                        @include('game.core.partials.equip.details.item-stat-details', ['item' => $adventure->itemReward])
                    @elseif (auth()->user->hasRole('Admin'))
                        <div class="alert alert-info"> This adventure has no quest item rewards. <a href="{{route('adventure.edit', [
                            'adventure' => $adventure->id,
                        ])}}">Assign one.</a> </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
