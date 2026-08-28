@extends('layouts.admin')

@section('content')
    <x-core.layout.info-container>
        <x-core.page.title title="Batch Crafting Monitoring" route="{{ route('home') }}" link="Back" />

        <div id="batch-crafting-monitoring"></div>
    </x-core.layout.info-container>
@endsection

@push('scripts')
    @vite('resources/js/admin/monitoring/batch-crafting-monitoring.tsx')
@endpush
