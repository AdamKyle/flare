@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{$marketBoard->item->affix_name}}"
            buttons="true"
            backUrl="{{route('game.current-listings', ['character' => auth()->user()->character->id])}}"
        >
            <div style="height: 300px; margin-bottom: 60px;">
                <canvas id="item-listing-data" width="400" height="400"></canvas>
            </div>

            <form method="post" action="{{route('game.update.current-listing', ['marketBoard' => $marketBoard])}}" class="mt-4">
                @csrf()
                <x-core.forms.input name="listed_price" label="Listed For" :model="$marketBoard" modelKey="listed_price" />
                <x-core.buttons.primary-button type="submit">
                    Update Listed Price
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>

            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById('item-listing-data').getContext('2d');

                const saleDataChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($saleData['labels']),
                        datasets: [{
                            label: 'Item Listed Data',
                            data: @json($saleData['data']),
                            backgroundColor: [
                                'rgba(59,90,154,1)',
                            ],
                            borderColor: [
                                'rgba(59,90,154,1)',
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        maintainAspectRatio: false,
                    }
                });
            });
        </script>
    @endpush
@endsection
