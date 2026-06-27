@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Application Logs"
            route="{{ route('home') }}"
            link="Back"
        />

        <div id="logs-dashboard"></div>
    </x-core.layout.info-container>
@endsection
