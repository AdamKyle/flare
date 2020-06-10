@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{$location->name}}</h4>
                    <p>{{$location->description}}</p>
                    <hr />
                    <dl>
                        <dt>Location X Coordinate:</dt>
                        <dd>{{$location->x}}</dd>
                        <dt>Location Y Coordinate:</dt>
                        <dd>{{$location->y}}</dd>
                        <dt>Is Port:</dt>
                        <dd>{{$location->is_port ? 'Yes' : 'No'}}</dd>
                    </dl>
                    <hr />
                    <a href="#" class="btn btn-primary mt-2">Edit Location</a>
                </div>
            </div>

            <div class="card mt-2">
                <div class="card-body">
                    <h4 class="card-title">Quest Item Reward</h4>
                    <p>All quest items are rewarded for just visiting the location.</p>
                    @if (!is_null($location->questRewardItem))
                    @include('game.items.partials.item-details', ['item' => $location->questRewardItem])
                    @include('game.core.partials.equip.details.item-stat-details', ['item' => $location->questRewardItem])
                    @else
                        <div class="alert alert-info"> This location has no quest item rewards. <a href="#">Assign one.</a> </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h4 class="card-title">Adventures</h4>
                    Show adventure details table.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
