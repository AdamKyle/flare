@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Items</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('items.create')}}" class="btn btn-primary float-right ml-2">Create</a>
        </div>
    </div>
    @livewire('admin.items.data-table', [
      'showSkillInfo' => true
    ])
@endsection
