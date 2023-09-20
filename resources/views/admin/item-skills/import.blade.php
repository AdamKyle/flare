@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Item Skills"
            buttons="true"
            backUrl="{{route('admin.items-skills.list')}}"
        >
            <div class="mt-4 mb-4">
                <x-core.alerts.info-alert title="ATTN!!">
                    If a skill has a parent skill that cannot be found, the parent skill and the parent skill level required will be
                    set to null.
                </x-core.alerts.info-alert>
            </div>
            <form class="mt-4" action="{{route('items.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="items_import">Items File</label>
                    <input id="items_import" type="file" class="form-control" name="items_import" />
                </div>
                <x-core.buttons.primary-button type="submit">Import Items</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
