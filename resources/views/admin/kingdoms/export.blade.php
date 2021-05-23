@extends('layouts.app')

@section('content')
    <x-core.page-title title="Export Kingdom Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <p>
                <strong>Please note</strong>: Editing the contents of the exported excel is not recommended. It is recommended that you
                make your changes in the system and then export to later import into production or to keep as a backup.
            </p>

            <p>
                Should anything go wrong with the import, changes will be rolled back.
            </p>

            <p>
                <strong>Do not</strong> use this to make changes to your kingdoms. If there is kingdom data already in the database your import
                will fail. Make changes in the system instead.
            </p>

            <p>
                The following export will export all kingdom data, including buildings, units and units assigned to buildings.
            </p>
        </div>

        <div class="text-center mt-4">
            <form method="POST" action="{{ route('kingdoms.export-data') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Export</button>
            </form>
        </div>
    </x-cards.card>
@endsection
