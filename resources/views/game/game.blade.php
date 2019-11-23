@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            Hello! {{$user->character->name}}
            <div id="game"></div>
        </div>
    </div>
</div>
@endsection
