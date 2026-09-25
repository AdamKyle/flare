@extends('layouts.game')

@section('content')
    <div id="onboarding-launcher" class="h-full min-h-0 w-full" data-character-id="{{ $user->character->id }}"></div>
@endsection

@push('game-app')
    @vite('resources/js/onboarding.ts')
@endpush
