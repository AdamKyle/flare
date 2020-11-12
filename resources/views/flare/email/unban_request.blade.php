@extends('layouts.minimum')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">{{$user->character->name}} Requesting to be unbanned</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('login')}}" class="btn btn-primary float-right ml-2">Login</a>
        </div>
    </div>

    <div class="card" style="padding: 5px">
        <p><strong>Original Reason:</strong> {{$user->banned_reason}}</p>

        <p clas="mt-2"><strong>Request</strong></p>
        <hr />
        <p>{{$user->un_ban_request}}</p>
    </div>
@endsection