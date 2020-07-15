@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-right">
            <a href="{{route('adventures.create')}}" class="btn btn-primary float-right ml-2">Create</a>
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Adventures</h4>

                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Location Name</th>
                                <th>Total Levels</th>
                                <th>Time Per Level</th>
                                <th>Item Reward<sup>*</sup></th>
                                <th>Skill XP Bonus<sup>*</sup></th>
                                <th>Gold Rush Chance<sup>*</sup></th>
                                <th>Item Find Chance</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($adventures as $adventure)
                                <tr>
                                    <td><a href="{{route('adventures.adventure', ['adventure' => $adventure])}}">{{$adventure->name}}</a></td>
                                    <td>{{$adventure->description}}</td>
                                    <td>
                                        {{implode(',', $adventure->locations->pluck('name')->toArray())}}
                                    </td>
                                    <td>{{$adventure->levels}}</td>
                                    <td>{{$adventure->time_per_level}}</td>
                                    <td>{{$adventure->itemReward->name}}</td>
                                    <td>{{$adventure->gold_rush_chance}}</td>
                                    <td>{{$adventure->item_find_chance}}</td>
                                    <td>{{$adventure->skill_exp_bonus}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="alert alert-info"><sup>*</sup>Indicates only if the character is completing this for the first time. Other wise it's random items and 1/3rd the bonuses.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
