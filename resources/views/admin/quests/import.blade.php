@extends('layouts.app')

@section('content')
    <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <x-core.page-title title="Import Quest Data" route="{{route('home')}}" color="success" link="Home">
        </x-core.page-title>

        <x-core.cards.card>
            <div class="alert alert-warning">
                <p>
                    Make sure you have imported items, skills, locations and so on that match this import data or we will ignore
                    specific aspects of the data and set it to "empty" for the respected value. IE, if you have a faction requirement and that map does not exist
                    then the quest(s) with that requirement will no longer have that as a requirement and you will have to go back and manually set it or re-import.
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
        </x-core.cards.card>
    </div>
@endsection
