@extends('layouts.minimum')

@section('content')
<div class="container justify-content-center">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$genericSubject}}</h4>
        </div>
        @if (!$dontShowLogin)
            <div class="col-md-6 align-self-right">
                <a href="{{route('login')}}" class="btn btn-primary float-right ml-2">Login!</a>
            </div>
        @endif
    </div>
    
    <div class="card" style="padding: 5px">
        @if ($user->hasRole('Admin'))
        <p>Hello Administrator,</p>
        @else
            <p>Hello {{$user->character->name}},</p>
        @endif
        <p>{{$genericMessage}}</p>
    </div>
    <div class="bt-3">
        <p class="text-muted">Do not reply to this email. This was an automated message.</p>
        <p class="text-muted">Your email is safe with us, we never use it for anything other then game related information.</p>
    </div>
</div>
@endsection