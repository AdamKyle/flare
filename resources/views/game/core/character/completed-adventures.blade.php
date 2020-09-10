@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="container justify-content-center">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix">
                    <h4 class="card-title">Previous Adventures</h4>
                    <hr />
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Completed</th>
                                <th>Last Completed Level</th>
                                <th>Total Levels</th>
                                <th>Collected Reward</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @foreach($adventures as $adventureLog)
                                <tr>
                                    <td><a href="{{route('game.completed.adventure', [
                                        'adventureLog' => $adventureLog
                                    ])}}">{{$adventureLog->adventure->name}}</a></td>
                                    <td>{{$adventureLog->completed ? 'Yes' : 'No'}}</td>
                                    <td>{{$adventureLog->last_completed_level}}</td>
                                    <td>{{$adventureLog->adventure->levels}}</td>
                                    <td>{{is_null($adventureLog->rewards) ? 'Yes' : 'No'}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
