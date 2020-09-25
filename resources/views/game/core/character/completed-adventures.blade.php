@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="container justify-content-center">
            <div class="row page-titles">
                <div class="col-md-6 align-self-right">
                    <h4 class="mt-2">Previous Adventures</h4>
                </div>
                <div class="col-md-6 align-self-right">
                    <a href="{{route('game')}}" class="btn btn-primary float-right ml-2">Home</a>
                </div>
            </div>
            @livewire('character.adventures.data-table', [
                'adventureLogs' => $logs
            ])
        </div>
    </div>
@endsection
