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

            <div class="card">
                <div class="card-body">
                    @livewire('character.inventory.data-table', [
                        'marketBoard' => true,
                    ])
                </div>
            </div>
        </div>
    </div>
@endsection
