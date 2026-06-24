@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Exploration Monitoring"
            route="{{ route('home') }}"
            link="Back"
        />

        <div id="exploration-monitoring"></div>
    </x-core.layout.info-container>
@endsection
