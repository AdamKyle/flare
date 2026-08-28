@extends('layouts.admin')

@section('content')
    <x-core.layout.info-container>
        <x-core.page.title title="Faction Loyalty Monitoring" route="{{ route('home') }}" link="Back" />

        <div id="faction-loyalty-monitoring"></div>
    </x-core.layout.info-container>
@endsection

@push('scripts')
    @vite('resources/js/admin/monitoring/faction-loyalty-monitoring.tsx')
@endpush
