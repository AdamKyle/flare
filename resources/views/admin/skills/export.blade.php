@extends('layouts.app')

@section('content')
    <x-core.page-title title="Export Skill Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning mt-3 mb-3">
            <h3>Caution!</h3>
            <p>Messing with the values in the exported file can break things for characters as no jobs are run when importing skills.</p>
            <p>Skills are generally imported before the launch of a game.</p>
        </div>
        <div class="text-center mt-4">
            <form method="POST" action="{{ route('skills.export-data') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Export</button>
            </form>
        </div>
    </x-cards.card>
@endsection
