@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Guide Quests"
            buttons="true"
            backUrl="{{route('admin.guide-quests')}}"
        >
            <div class="mt-4 mb-4">
                <x-core.alerts.info-alert title="ATTN!">
                    Do not import guide quests after player shave registered unless it to fix spelling mistakes or add additional ones.
                    You cannot change the order or import a sheet with new quests in between potentially already completed quests.
                </x-core.alerts.info-alert>
            </div>
            <form class="mt-4" action="{{route('admin.guide-quests.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="guide_quests_import">Guide Quests File</label>
                    <input id="guide_quests_import" type="file" class="form-control" name="guide_quests_import" />
                </div>
                <x-core.buttons.primary-button type="submit">Import Guide Quests</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
