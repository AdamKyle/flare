@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-12">
            @livewire('market.current-listings', [
                'character' => $character
            ])
        </div>
    </div>
@endsection
