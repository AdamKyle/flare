@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Market"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>

        <x-core.alerts.info-alert title="ATTN!">
            This table is not live.
        </x-core.alerts.info-alert>

        <div id="market-listings" style="height: 300px;"></div>

        @livewire('market.all-listings')
    </x-core.layout.info-container>

    @push('scripts')
        <!-- Charting library -->
        <script src="https://unpkg.com/echarts/dist/echarts.min.js"></script>
        <!-- Chartisan -->
        <script src="https://unpkg.com/@chartisan/echarts/dist/chartisan_echarts.js"></script>
        <!-- Your application script -->
        <script>
            const chart = new Chartisan({
                el: '#market-listings',
                url: "@chart('market_board_history')",
                hooks: new ChartisanHooks()
                    .legend()
                    .colors()
                    .tooltip()
                    .datasets([{ type: 'line', fill: false }]),
            });
        </script>
    @endpush
@endsection
