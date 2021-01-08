@extends('layouts.app')

@section('content')
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Battle Simulation Data For: {{$monster->name}}</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('monsters.list')}}" class="btn btn-success float-right ml-2">Monsters</a>
            <a href="{{route('monsters.monster', ['monster' => $monster->id])}}" class="btn btn-primary float-right ml-2">View Monster</a>
        </div>
    </div>
    <div id="chart" style="height: 300px;"></div>

    @livewire('admin.character-modeling.simulations.battle.data-table', [
        'monster' => $monster
    ])
@endsection
@push('scripts')
    <script>
      const chart = new Chartisan({
        el: '#chart',
        url: "@chart('battle_simmulation_chart')" + "?monsterId={{$monster->id}}",
        hooks: new ChartisanHooks().datasets('bar').tooltip().legend(),
      });
    </script>
@endpush
