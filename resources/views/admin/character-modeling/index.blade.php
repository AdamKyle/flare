@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-right">
            <h4 class="mt-2">Character Modeling</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
        </div>
    </div>
    <hr />
    <x-cards.card-with-title title="{{$cardTitle}}">

        @if (!$hasSnapShots)
            <div class="text-center">
                <a class="btn btn-primary" href="{{ route('admin.character.modeling.generate') }}"
                    onclick="event.preventDefault();
                            document.getElementById('generate-form').submit();">
                    Generate Character Modeling
                </a>

                <form id="generate-form" action="{{ route('admin.character.modeling.generate') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>
        @else
            <div id="character-modeling"></div>
        @endif
        
    </x-cards.card-with-title>
@endsection
