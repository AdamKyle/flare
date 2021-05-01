@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Unit Movement Log"
        route="{{route('game')}}"
        link="Game"
        color="primary"
    ></x-core.page-title>

    <div id="unit-movement" data-character="{{$character->id}}" data-user="{{$character->user->id}}"></div>
@endsection

@push('scripts')
    <script>
        renderKingdomMovement('unit-movement');
    </script>
@endpush
