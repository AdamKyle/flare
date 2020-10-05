@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="container justify-content-center">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">Shop - Selling</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            </div>
        </div>
    
        <div class="card">
            <div class="card-body">
                @if ($isLocation)
                    <p>
                        You enter the old and musty shop. Along the walls you an see various weapons, armor
                        and other types of items that might benifit you on your journies.
                    </p>
                    <p>
                        Counting your gold, you walk in with confidence, knowing you will walk out with
                        better gear. Knowing ... Your enemies stand no chance.
                    </p>
                    <p>
                        As you enter, you see an old man behind a worn counter. He smiles warmly at you. Welcoming you:
                    </p>

                    <p><strong>Shop Keeper</strong>: <em>Hello! welcome! what can I get for you?</em></p>
                @else
                    <p>On your journey you come across a merhant on the road. He is carrying his bag full of trinkets and goodies.</p>
                    <p>As you approach, he takes off his backpack and warmly greets you:</p>
                    <p><strong>Shop Keeper</strong>: <em>These roads are dangerous my friend! What can I get you?</em></p>
                @endif
                
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <p><strong>Your Gold</strong>: <span class="color-gold">{{$gold}}<span></p>
            </div>
        </div>

        <h4>Your Items To Sell</h4>
        <div class="alert alert-warning">
            Clicking "Sell All" will sell everything listed below. No quest items or currently equipped items will be sold.
        </div>
        @livewire('character.inventory.data-table', [
            'batchSell' => true 
        ])
    </div>    
</div>

@endsection
