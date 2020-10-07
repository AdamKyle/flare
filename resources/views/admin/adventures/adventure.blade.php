@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="container justify-content-center">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$adventure->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                @if (auth()->user()->hasRole('Admin'))
                    <a href="{{route('adventures.list')}}" class="btn btn-primary float-right ml-2">Back</a>
                @else
                    <a href="{{route('game')}}" class="btn btn-primary float-right ml-2">Back</a>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-body">
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
        <h4>Found At:</h4>
        @livewire('admin.locations.data-table', [
            'adventureId' => $adventure->id,
        ])
        <h4>With Monsters:</h4>
        <p class="text-muted mb-2" style="font-size: 12px;"><em>Monsters are selected at random for each adventure level.</em></p>
        @livewire('admin.monsters.data-table', [
            'adventureId' => $adventure->id
        ])
        <h4>Rewards: {{$adventure->itemReward->name}}</h4>
        <em class="text-muted" style="font-size: 12px;">All quest items are rewarded once for completing the adventure the first time only.</em>
        <div class="card mt-2">
            <div class="card-body">
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
