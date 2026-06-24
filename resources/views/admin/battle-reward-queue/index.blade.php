@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Character Reward Queue"
            route="{{ route('home') }}"
            link="Back"
        />

        <div id="character-reward-queue"></div>
    </x-core.layout.info-container>
@endsection
