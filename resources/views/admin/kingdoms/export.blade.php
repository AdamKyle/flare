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
                It is not recommended that you use this to make changes to production kingdoms as that can affect all players,
                sending out tons of emails or notifications. The purpose of this export is to help you get started in production before
                you launch.
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
