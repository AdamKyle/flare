@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-6 align-self-left">
                <h4 class="mt-3">Quests</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
                <a href="{{route('quests.create')}}" class="btn btn-primary float-right ml-2">Create</a>
                <a href="{{route('quests.import')}}" class="btn btn-primary float-right ml-2">Import</a>
                <a href="{{route('quests.export')}}" class="btn btn-primary float-right ml-2">Export</a>
            </div>
        </div>
        <hr />
        @livewire('admin.quests.data-table')
    </div>
@endsection
