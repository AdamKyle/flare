@extends('layouts.app')

@section('content')
    <x-core.page-title title="Import Item Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <p>
                <strong>Please note</strong>: If an item has an affix or a affects a skill that no longer exists, the item will be ignored.
            </p>
        </div>

        <div class="mt-4">
            <form class="mt-4" action="{{route('items.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="items_import">Item's File</label>
                    <input type="file" class="form-control" id="items_import" aria-describedby="items_import" name="items_import">
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
    </x-cards.card>
@endsection
