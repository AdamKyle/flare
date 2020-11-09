@extends('layouts.minimum')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">Password Reset</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('password.reset', $token)}}" class="btn btn-primary float-right ml-2">Reset My Password</a>
        </div>
    </div>

    <div class="card" style="padding: 5px">
        <p>Hello {{$user->character->name}}, You recently requested your password to be reset by an administrator.</p>
    </div>
    <div class="bt-3">
        <p class="text-muted">Do not reply to this email. This was an automated message.</p>
        <p class="text-muted">Your email is safe with us, we never use it for anything other then game related information.</p>
    </div>
@endsection