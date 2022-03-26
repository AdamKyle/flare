@extends('layouts.app')

@section('content')
    <x-core.page-title title="Export Item Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="text-center mt-4">
            <div class="clearfix" style="margin: 0 auto;">
                <form method="POST" action="{{ route('items.export-data') }}" class="tw-mt-4">
                    @csrf
                    <button type="submit" class="btn btn-primary">Export All (without affixes)</button>
                </form>
                <form method="POST" action="{{ route('items.export-data') }}" class="tw-mt-4">
                    @csrf
                    <input value="weapons" name="type_to_export" type="hidden" />
                    <button type="submit" class="btn btn-primary">Export Weapons Only</button>
                </form>
                <form method="POST" action="{{ route('items.export-data') }}" class="tw-mt-4">
                    @csrf
                    <input value="armour" name="type_to_export" type="hidden" />
                    <button type="submit" class="btn btn-primary">Export Armour Only</button>
                </form>
                <form method="POST" action="{{ route('items.export-data') }}" class="tw-mt-4">
                    @csrf
                    <input value="artifacts" name="type_to_export" type="hidden" />
                    <button type="submit" class="btn btn-primary">Export Artifacts Only</button>
                </form>
                <form method="POST" action="{{ route('items.export-data') }}" class="tw-mt-4">
                    @csrf
                    <input value="rings" name="type_to_export" type="hidden" />
                    <button type="submit" class="btn btn-primary">Export Rings Only</button>
                </form>
                <form method="POST" action="{{ route('items.export-data') }}" class="tw-mt-4">
                    @csrf
                    <input value="quest" name="type_to_export" type="hidden" />
                    <button type="submit" class="btn btn-primary">Export Quest Items Only</button>
                </form>
                <form method="POST" action="{{ route('items.export-data') }}" class="tw-mt-4">
                    @csrf
                    <input value="alchemy" name="type_to_export" type="hidden" />
                    <button type="submit" class="btn btn-primary">Export Alchemy Items Only</button>
                </form>
                <form method="POST" action="{{ route('items.export-data') }}" class="tw-mt-4">
                    @csrf
                    <input value="trinket" name="type_to_export" type="hidden" />
                    <button type="submit" class="btn btn-primary">Export Trinkets Only</button>
                </form>
            </div>
        </div>
    </x-cards.card>
@endsection
