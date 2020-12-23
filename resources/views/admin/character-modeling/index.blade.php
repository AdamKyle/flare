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
            <div id="character-modeling">
                <div class="row">
                    @foreach ($snapShots as $snapShot)
                        <div class="col-md-3 mb-2">
                            <x-cards.card-with-title title="{{$snapShot->character->name}}" route="{{route('admin.character.modeling.sheet', ['character' => $snapShot->character->id])}}" class="btn btn-primary btn-sm">
                                <h4>Generated test Character</h4>
                                <hr />
                                <dl>
                                    <dt>Level:</dt>
                                    <dd>{{$snapShot->character->level}}</dd>
                                    <dt>Class:</dt>
                                    <dd>{{$snapShot->character->class->name}}</dd>
                                    <dt>Race:</dt>
                                    <dd>{{$snapShot->character->race->name}}</dd>
                                    <dt>Damage Stat:</dt>
                                    <dd>{{$snapShot->character->damage_stat}}</dd>
                                    <dt>str:</dt>
                                    <dd>{{$snapShot->character->getInformation()->statMod('str')}}</dd>
                                    <dt>dur:</dt>
                                    <dd>{{$snapShot->character->getInformation()->statMod('dur')}}</dd>
                                    <dt>dex:</dt>
                                    <dd>{{$snapShot->character->getInformation()->statMod('dex')}}</dd>
                                    <dt>int:</dt>
                                    <dd>{{$snapShot->character->getInformation()->statMod('int')}}</dd>
                                    <dt>chr:</dt>
                                    <dd>{{$snapShot->character->getInformation()->statMod('chr')}}</dd>
                                </dl>
                            </x-cards.card-with-title>
                        </div>
                    @endforeach

                    {{$snapShots->links()}}
                </div>
            </div>
        @endif
        
    </x-cards.card-with-title>
@endsection
