@extends('layouts.app')

@section('content')
    <x-core.page-title title="Export Monster Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="text-center mt-4">
            <form method="POST" action="{{ route('monsters.export-data') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Export</button>
            </form>
        </div>
    </x-cards.card>
@endsection
