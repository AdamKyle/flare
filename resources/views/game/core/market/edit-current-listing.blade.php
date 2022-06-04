@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <h2 class="mt-2 font-light">
            <x-item-display-color :item="$marketBoard->item" />
        </h2>

        <div class="relative">
            <div class="float-right">
                <x-core.buttons.link-buttons.success-button
                    href="{{route('game.current-listings', ['character' => auth()->user()->character->id])}}"
                    css="tw-ml-2"
                >
                    Back
                </x-core.buttons.link-buttons.success-button>
            </div>
        </div>

        <div class="mb-4">
            <div id="market-history" style="height: 300px;"></div>
        </div>

        <form method="post" action="{{route('game.update.current-listing', ['marketBoard' => $marketBoard])}}">
            <x-core.forms.input name="listed_price" label="Listed For" :model="$marketBoard" modelKey="listed_price" />
            <x-core.buttons.primary-button type="submit">
                Update Listed Price
            </x-core.buttons.primary-button>
        </form>
    </x-core.layout.info-container>

    @push('scripts')
        <!-- Charting library -->
        <script src="https://unpkg.com/echarts/dist/echarts.min.js"></script>
        <!-- Chartisan -->
        <script src="https://unpkg.com/@chartisan/echarts/dist/chartisan_echarts.js"></script>
        <!-- Your application script -->
        <script>
            const itemId = {{$marketBoard->item_id}};
            const chart = new Chartisan({
                el: '#market-history',
                url: "@chart('market_board_item_history')" + "?item_id=" + itemId,
                hooks: new ChartisanHooks()
                    .legend()
                    .colors()
                    .tooltip()
                    .datasets([{ type: 'line', fill: false }]),
            });
        </script>
    @endpush
@endsection
