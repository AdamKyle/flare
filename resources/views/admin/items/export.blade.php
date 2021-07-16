@extends('layouts.app')

@section('content')
    <x-core.page-title title="Export Item Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="text-center mt-4">
            <div class="clearfix" style="width: 250px; margin: 0 auto;">
                <form method="POST" action="{{ route('items.export-data') }}" class="float-left">
                    @csrf
                    <button type="submit" class="btn btn-primary">Export</button>
                </form>
                <form method="POST" action="{{ route('items.export-data') }}" class="float-right">
                    @csrf
                    <input value="affixes_only" name="affixes" type="hidden" />
                    <button type="submit" class="btn btn-primary">Export Affixes only</button>
                </form>
            </div>
        </div>
    </x-cards.card>
@endsection
