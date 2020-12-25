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
                <a href="{{route('adventures.adventure', ['adventure' => $adventureData['adventure_id']])}}" class="btn btn-primary float-right ml-2">View Adventure</a>
            </div>
        </div>
        <hr />
        <div class="log-text">
            @foreach($adventureData['snap_shot_data'] as $index => $levelData)
                @foreach($levelData as $levelName => $logData)
                    <x-cards.card-with-title title="Level {{$index + 1}}">
                        <div class="pt-4">
                            @include('admin.character-modeling.partials.battle-data', [
                                'data' => $logData['logs']
                            ])
                        </div>

                        @if ($logData['took_to_long'])
                            <div class="alert alert-error mb-2 mt-2">
                                This floor took too long.
                            </div>
                        @endif

                        @if ($logData['character_dead'])
                            <div class="alert alert-error mb-2 mt-2">
                                Character died on this floor.
                            </div>
                        @endif

                        @if (!$logData['took_to_long'] && !$logData['character_dead'])
                            <div class="alert alert-success mb-2 mt-2">
                                Character completed floor.
                            </div>
                        @endif
                    </x-cards.card-with-title>
                @endforeach
            @endforeach
        </div>
    </div>
@endsection
