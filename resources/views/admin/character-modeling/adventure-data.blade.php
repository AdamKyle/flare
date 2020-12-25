@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Adventure Simulation Data For: {{$adventure->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('adventures.adventure', ['adventure' => $adventure->id])}}" class="btn btn-primary float-right ml-2">View Adventure</a>
        </div>
    </div>

    @livewire('admin.character-modeling.simulations.adventure.data-table', [
        'adventure' => $adventure
    ])
@endsection
