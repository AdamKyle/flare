@extends('layouts.app')

@section('content')
    <x-core.page-title title="Import Quest Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <p>
                <strong>Please note</strong>: If a NPC for the quest does not exist the quest will be skipped upon importing. If the
                quest requires an item and the item does exist, we will skip. If the quest rewards an item that does not exist, we will skip
                the quest.
            </p>
            <p>
                Required items are under <code>item_id</code>
            </p>
        </div>

        <div class="mt-4">
            <form class="mt-4" action="{{route('quests.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="quests_import">Quests File</label>
                    <input type="file" class="form-control" id="quests_import" aria-describedby="quests_import" name="quests_import">
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
    </x-cards.card>
@endsection
