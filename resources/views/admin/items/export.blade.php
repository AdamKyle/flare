@extends('layouts.app')

@section('content')
    <x-core.page-title title="Export Item Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="text-center mt-4">
            <form method="POST" action="{{ route('items.export-data') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Export</button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}">
                @csrf
                <input value="affixes_only" name="affixes" type="hidden" />
                <button type="submit" class="btn btn-primary">Export Affixes only</button>
            </form>
        </div>
    </x-cards.card>
@endsection
