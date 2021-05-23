@extends('layouts.app')

@section('content')
    <x-core.page-title title="Import Monster Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <p>
                <strong>Please note</strong>: If an monster has a skill or item reward that does not exist, that monster will be skipped.
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
