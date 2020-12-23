@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row page-titles">
            <div class="col-md-6 align-self-left">
                <h4 class="mt-3">Data For Fight</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
                <a href="{{route('admin.character.modeling.sheet', ['character' => $characterId])}}" class="btn btn-primary float-right ml-2">View Character</a>
                <a href="{{route('monsters.monster', ['monster' => $monsterId])}}" class="btn btn-primary float-right ml-2">View Monster</a>
            </div>
        </div>
        <hr />
        <div class="log-text">
            @foreach($battleData as $key => $data)
                @if (is_array($data))
                    <x-cards.card-with-title title="Battle Results" class="log-text">
                        <div class="mt-3 mb-3">
                            @foreach ($data as $index => $battleData)
                                @if (is_array($battleData))    
                                    @php $isMonster = $battleData['is_monster'] @endphp
                                
                                    @if (isset($battleData['message']))
                                        <p class="text-center {{$isMonster ? 'monster-color' : 'character-color'}}">{{$battleData['message']}}</p>
                                    @endif
        
                                    @if (isset($battleData['messages']))
                                        @foreach($battleData['messages'] as $key => $value)
                                            <p class="text-center {{$isMonster ? 'monster-color' : 'character-color'}}">{{$value[0]}}</p>
                                        @endforeach
                                    @endif
                                @endif
                            @endforeach
                            <hr />
                            <dl>
                                <dd>Character Died?</dd>
                                <dt>{{$data['character_dead'] ? 'Yes' : 'No'}}</dt>
                                <dd>Monster Died?</dd>
                                <dt>{{$data['monster_dead'] ? 'Yes' : 'No'}}</dt>
                            </dl>
                        </div>
                    </x-cards.card-with-title>
                @endif
            @endforeach
        </div>
    </div>
@endsection
