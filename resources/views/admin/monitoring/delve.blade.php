@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Delve Monitoring"
            route="{{ route('home') }}"
            link="Back"
        />

        <div id="delve-monitoring"></div>
    </x-core.layout.info-container>
@endsection
