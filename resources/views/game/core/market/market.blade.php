@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Market"
      buttons="true"
      backUrl="{{route('game')}}"
    >
      <x-core.alerts.info-alert title="ATTN!">
        This table and chart are not live.
      </x-core.alerts.info-alert>

      <div style="height: 300px; margin-bottom: 60px">
        <canvas id="historical-listing-data" width="400" height="400"></canvas>
      </div>
    </x-core.cards.card-with-title>

    @livewire('market.all-listings')
  </x-core.layout.info-container>

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const ctx = document
          .getElementById('historical-listing-data')
          .getContext('2d');

        const historicalDataChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: @json($marketChartData['labels']),
            datasets: [
              {
                label: 'Selling Price',
                data: @json($marketChartData['data']),
                backgroundColor: ['rgba(59,90,154,1)'],
                borderColor: ['rgba(59,90,154,1)'],
                borderWidth: 2,
              },
            ],
          },
          options: {
            scales: {
              y: {
                beginAtZero: true,
              },
            },
            maintainAspectRatio: false,
          },
        });
      });
    </script>
  @endpush
@endsection
