@extends('layouts.app')

@section('content')
    <x-core.page-title
        title="Units In Movement"
        route="{{route('game')}}"
        link="Game"
        color="primary"
    ></x-core.page-title>
    <div class="alert alert-info">
        <strong>Please note:</strong> This table is live.
    </div>
    <div id="unit-movement" data-character="{{$character->id}}" data-user="{{$character->user->id}}"></div>
@endsection

@push('scripts')
    <script>
        renderKingdomMovement('unit-movement');
    </script>
@endpush
