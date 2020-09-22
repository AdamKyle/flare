@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">Maps</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('maps.upload')}}" class="btn btn-primary float-right ml-2">Upload New</a>
        </div>
    </div>
    @livewire('admin.maps.data-table')
</div>
@endsection
