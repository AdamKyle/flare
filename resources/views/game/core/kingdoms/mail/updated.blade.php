@extends('layouts.minimum')

@section('content')
<div class="container justify-content-center">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">Kingdoms</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('login')}}" class="btn btn-primary float-right ml-2">Login!</a>
        </div>
    </div>
    
    <div class="card" style="padding: 5px">
        <p>Hello {{$user->character->name}}, the following are a list of kingdoms that have been updated.</p>
        <dl>
            @foreach ($kingdomData as $kingdoms)
                <dt><strong>Name</strong>:</dt>
                <dd>{{$kingdoms['name']}}</dd>

                <dt><strong>X/Y</strong>:</dt>
                <dd>{{$kingdoms['x_position']}}/{{$kingdoms['y_position']}}</dd>
            @endforeach
        </dl>
    </div>
    <div class="bt-3">
        <p class="text-muted">Do not reply to this email. This was an automated message. If you want to stop recieveing these you can visit your <a href="#">settings page</a> and make the appropriate adjustments!</p>
        <p class="text-muted">Your email is safe with us, we never use it for anything other then game related information.</p>
    </div>
</div>
@endsection