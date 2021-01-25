@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">{{!is_null($item) ? 'Edit item: ' . $item->affix_name : 'Create item'}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('items.list')}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    @livewire('core.form-wizard', [
        'views' => [
            'admin.items.partials.item-details',
            'admin.items.partials.item-modifiers',
        ],
        'model'     => $item,
        'modelName' => 'item',
        'steps' => [
            'Item Details',
            'Item Modifiers',
        ],
        'finishRoute' => 'items.list',
        'editing' => $editing,
    ])
@endsection
