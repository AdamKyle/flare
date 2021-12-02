@extends('layouts.app')

@section('content')
    <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <div class="row page-titles">
            <div class="col-md-6 align-self-left">
                <h4 class="mt-3">Buildings</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
                <a href="{{route('buildings.create')}}" class="btn btn-primary float-right ml-2">Create</a>
            </div>
        </div>
        @livewire('admin.kingdoms.buildings.data-table')
    </div>
@endsection
