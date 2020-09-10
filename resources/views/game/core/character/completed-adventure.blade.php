@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="container justify-content-center">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{$adventureLog->adventure->name}}</h4>
                    <p>
                        {{$adventureLog->adventure->description}}
                    </p>
                    <hr />
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>Log Entry</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @php $count = 1; @endphp
                            @foreach($adventureLog->logs as $logName => $log)
                                <tr>
                                    <td><a href={{route('game.completed.adventure.logs', [
                                        'adventureLog' => $adventureLog->id,
                                        'name'         => $logName
                                    ])}}>{{'Log Entry ' . $count}}</a></td>
                                </tr>
                                @php $count++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
