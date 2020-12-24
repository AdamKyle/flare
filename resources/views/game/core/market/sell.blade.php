@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <x-core.page-title 
                title="Sell items on market board"
                route="{{url()->previous()}}"
                link="Back"
                color="success"
            ></x-core.page-title>

            @livewire('character.inventory.data-table', [
                'marketBoard' => true,
                'character' => auth()->user()->character
            ])
        </div>
    </div>
@endsection
