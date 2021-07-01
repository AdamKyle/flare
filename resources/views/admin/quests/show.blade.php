@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$quest->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            </div>
        </div>
        <hr />
        <x-cards.card-with-title title="Details">
            Quest Info ....See dump
            @dump($quest);
        </x-cards.card-with-title>
    </div>
@endsection
