@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page.title
            title="Batch Crafting Monitoring"
            route="{{ route('home') }}"
            link="Back"
        />

        <div id="batch-crafting-monitoring"></div>
    </x-core.layout.info-container>
@endsection
