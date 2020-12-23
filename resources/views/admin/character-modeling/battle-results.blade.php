@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row page-titles">
            <div class="col-md-6 align-self-left">
                <h4 class="mt-3">Data For: {{$battleData['monster_name']}} Fight</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
                <a href="{{route('admin.character.modeling.sheet', ['character' => $characterId])}}" class="btn btn-primary float-right ml-2">View Character</a>
                <a href="{{route('monsters.monster', ['monster' => $monsterId])}}" class="btn btn-primary float-right ml-2">View Monster</a>
            </div>
        </div>
        <hr />
        <div class="log-text">
            <x-cards.card-with-title title="Battle Results" class="log-text">
                <div class="mt-3 mb-3">
                    @foreach($battleData as $key => $data)
                        @if (is_array($data))
                            @php $isMonster = $data['is_monster'] @endphp
                            
                            @if (isset($data['message']))
                                <p class="text-center {{$isMonster ? 'monster-color' : 'character-color'}}">{{$data['message']}}</p>
                            @endif

                            @if (isset($data['messages']))
                                @foreach($data['messages'] as $key => $value)
                                    <p class="text-center {{$isMonster ? 'monster-color' : 'character-color'}}">{{$value[0]}}</p>
                                @endforeach
                            @endif
                        @endif
                    @endforeach
                </div>
            </x-cards.card-with-title>
        </div>
    </div>
@endsection
