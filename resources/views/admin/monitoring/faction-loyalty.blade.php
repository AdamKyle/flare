@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Faction Loyalty Monitoring"
            route="{{ route('home') }}"
            link="Back"
        />

        <div id="faction-loyalty-monitoring"></div>
    </x-core.layout.info-container>
@endsection
