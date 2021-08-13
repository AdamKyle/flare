@extends('layouts.app')

@section('content')
    <x-core.page-title title="Import NPC Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <p>
                <strong>Please note</strong>: If a map does not exist for the NPC, the NPC will be ignored
            </p>
        </div>

        <div class="mt-4">
            <form class="mt-4" action="{{route('npcs.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="npcs_import">Npcs File</label>
                    <input type="file" class="form-control" id="npcs_import" aria-describedby="npcs_import" name="npcs_import">
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
    </x-cards.card>
@endsection
