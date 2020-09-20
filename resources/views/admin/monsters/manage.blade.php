@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-12 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    {{-- <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{is_null($adventure) ? 'Create Adventure' : 'Edit Adventure: ' . $adventure->name}}</h4>
                </div>
            </div>
        </div>
    </div> --}}
    @livewire('admin.monsters.create')
</div>
@endsection
