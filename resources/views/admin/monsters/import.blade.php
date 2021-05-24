@extends('layouts.app')

@section('content')
    <x-core.page-title title="Import Monster Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <p>
                <strong>Please note</strong>: If a monster has an quest item that doesn't exist the monster will be skipped.
                If the monster has a skill that doesn't exist, the skill for that monster will be skipped.
            </p>
        </div>

        <div class="mt-4">
            <form class="mt-4" action="{{route('monsters.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="monsters_import">Item's File</label>
                    <input type="file" class="form-control" id="monsters_import" aria-describedby="monsters_import" name="monsters_import">
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
    </x-cards.card>
@endsection
