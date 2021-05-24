@extends('layouts.app')

@section('content')
    <x-core.page-title title="Import Affix Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <p>
                <strong>Please note</strong>: If an affix affects a skill that does not exist, the affix will be skipped.
            </p>
        </div>

        <div class="mt-4">
            <form class="mt-4" action="{{route('affixes.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="affixes_import">Item's File</label>
                    <input type="file" class="form-control" id="affixes_import" aria-describedby="affixes_import" name="affixes_import">
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
    </x-cards.card>
@endsection
