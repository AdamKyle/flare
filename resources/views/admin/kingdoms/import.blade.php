@extends('layouts.app')

@section('content')
    <x-core.page-title title="Import Kingdom Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <p>
                <strong>Please note</strong>: If you already have existing kingdom data, we will not import the excel sheet.
            </p>
            <p>
                If you are looking to make changes, please do so in the system. This is only to be used to quickly set up a production or development environment.
            </p>

            <p>
                Should we fail to import we will roll back all changes.
            </p>
        </div>

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
    </x-cards.card>
@endsection
