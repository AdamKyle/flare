@extends('layouts.app')

@section('content')
    <x-core.page-title title="Import Skills Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <x-cards.card>
        <div class="alert alert-warning">
            <h3>Caution!</h3>
            <p>Messing with the values in the exported file can break things for characters as no jobs are run when importing skills.</p>
            <p>Skills are generally imported before the launch of a game.</p>
        </div>

        <div class="mt-4">
            <form class="mt-4" action="{{route('skills.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="skills_import">Item's File</label>
                    <input type="file" class="form-control" id="skills_import" aria-describedby="skills_import" name="skills_import">
                </div>
                <button type="submit" class="btn btn-primary">Import</button>
            </form>
        </div>
    </x-cards.card>
@endsection
