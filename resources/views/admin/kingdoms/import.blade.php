@extends('layouts.app')

@section('content')
    <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <x-core.page-title
          title="Import Kingdom Data"
          route="{{route('home')}}"
          color="success"
          link="Home"
        > </x-core.page-title>

        <x-core.cards.card>
            <x-core.alerts.warning-alert title="ATTN!">
                <p>
                    Do not use this to make changes to kingdom buildings stats or assigned units. Only use this if you have
                    no players in game or you have new buildings to introduce.
                </p>
                <p>
                    Import will only assign new buildings to players kingdoms.
                </p>
            </x-core.alerts.warning-alert>

            <div class="mt-4">
                <form class="mt-4" action="{{route('kingdoms.import-data')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="kingdom_import">Kingdom File</label>
                        <input type="file" class="form-control" id="kingdom_import" aria-describedby="kingdom_import" name="kingdom_import">
                    </div>
                    <button type="submit" class="btn btn-primary">Import</button>
                </form>
            </div>
        </x-core.cards.card>
    </div>
@endsection
