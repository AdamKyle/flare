@extends('layouts.app')

@section('content')
    <x-core.page-title title="Statistical Data" route="{{route('home')}}" color="success" link="Home">
    </x-core.page-title>

    <div class="alert alert-info mt-2 mb-3">
        Charts are not live.
    </div>

    <x-cards.card-with-title title="Site Information">
        <div class="row">
            <div class="col-md-6">
                <div id="site-accessed"></div>
            </div>
            <div class="col-md-6">
                <div id="registered-users"></div>
            </div>
        </div>
    </x-cards.card-with-title>
    <hr />
    <div class="row">
        <div class="col-md-6">
            <x-cards.card-with-title title="Average Character Information">
                <div class="alert alert-info mt-2 mb-3">Average of all Characters</div>
                <dl>
                    <dt>Average Character Level</dt>
                    <dd>{{$averageCharacterLevel}}</dd>
                    <dt>Average Character Gold</dt>
                    <dd>{{$averageCharacterGold}}</dd>
                    <dt>Richest Character</dt>
                    <dd><a href="{{route('users.user', ['user' => $richestCharacter->user->id])}}">{{$richestCharacter->name}}</a></dd>
                    <dt>Highest Level Character</dt>
                    <dd><a href="{{route('users.user', ['user' => $highestLevelCharacter->user->id])}}">{{$highestLevelCharacter->name}}</a></dd>
                </dl>
            </x-cards.card-with-title>
        </div>
        <div class="col-md-6">
            <x-cards.card-with-title title="Kingdom Data">
                <dl>
                    <dt>Kingdom Count (Across All Maps)</dt>
                    <dd>{{$kingdomCount}}</dd>
                </dl>
            </x-cards.card-with-title>
        </div>
    </div>
    <hr />
    <x-cards.card-with-title title="Characters Gold (Not Live)">
        <div id="character-gold"></div>
    </x-cards.card-with-title>
@endsection

@push('head')
    <script src={{mix('js/admin-statistics.js')}} type="text/javascript"></script>
@endpush

@push('scripts')
    <script>
        renderStatsAllTime('site-accessed', 'Logged In (All Time)', 'Login (All Time)', '/api/admin/site-statistics/all-time-sign-in');
        renderStatsAllTime('registered-users', 'Registered (All Time)', 'Registered (All Time)', '/api/admin/site-statistics/all-time-register');
        renderStatsAllTime('character-gold', 'Gold', 'Gold', '/api/admin/site-statistics/all-characters-gold');
    </script>
@endpush
