@extends('layouts.app')

@section('content')
    <x-core.page-title title="Export NPC Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="text-center mt-4">
            <div class="clearfix" style="width: 250px; margin: 0 auto;">
                <form method="POST" action="{{ route('npcs.export-data') }}" class="float-left">
                    @csrf
                    <button type="submit" class="btn btn-primary">Export</button>
                </form>
            </div>
        </div>
    </x-cards.card>
@endsection
