@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 align-self-left">
                <h4 class="mt-3">NPC's</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
                <a href="{{route('npcs.create')}}" class="btn btn-primary float-right ml-2">Create</a>
                <a href="{{route('npcs.import')}}" class="btn btn-primary float-right ml-2">Import</a>
                <a href="{{route('npcs.export')}}" class="btn btn-primary float-right ml-2">Export</a>
            </div>
        </div>
        <hr />
        @livewire('admin.npcs.data-table')

    </div>
@endsection
