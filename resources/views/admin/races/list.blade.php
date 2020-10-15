@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Races</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('races.create')}}" class="btn btn-primary float-right ml-2">Create</a>
        </div>
    </div>
    @livewire('admin.races.data-table')
</div>
@endsection
