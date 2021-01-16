@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Units</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('units.create')}}" class="btn btn-primary float-right ml-2">Create</a>
        </div>
    </div>
    @livewire('admin.kingdoms.units.data-table')
@endsection
