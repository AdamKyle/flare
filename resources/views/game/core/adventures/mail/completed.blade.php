@extends('layouts.minimum')

@section('content')
<div class="container justify-content-center">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$adventureLog->adventure->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('game.current.adventure')}}" class="btn btn-primary float-right ml-2">View Log</a>
        </div>
    </div>
    
    <div class="card" style="padding: 5px">
        <p>Hello {{$character->name}}, some basic adventure information is listed below based on your latest adventure log.</p>
        <p>You wen't logged in so we thought we would send you this email with the details below:</p>
        <dl>
            <dt>Completed:</dt>
            <dd>{{$adventureLog->complete ? 'Yes' : 'No'}}</dd>
            @if (!$adventureLog->complete)
                <dt>Reason:</dt>
                <dd>You died.</dd>
            @endif
            <dt>Last level completed:</dt>
            <dd>{{$adventureLog->last_completed_level}}</dd>
            <dt>Total adventure levels:</dt>
            <dd>{{$adventureLog->adventure->levels}}</dd>
        </dl>
    </div>
    <div class="bt-3">
        <p class="text-muted">Do not reply to this email. This was an automated message. If you want to stop recieveing these you can visit your <a href="#">settings page</a> and make the appropriate adjustments!</p>
        <p class="text-muted">Your email is safe with us, we never use it for anything other then game related information.</p>
    </div>
</div>
@endsection