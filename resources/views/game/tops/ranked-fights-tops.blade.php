@extends('layouts.app')

@section('content')
    <div class="w-full lg:w-3/4 ml-auto mr-auto">

        <x-core.page-title
            title="Ranked Fights Tops"
            route="{{route('game')}}"
            link="Game"
            color="primary"
        ></x-core.page-title>

        <div class="my-4">
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
            <p>
                <a href="/information/rank-fights">Rank Fights</a> are some of the strongest creatures in the game,
                who live at the Underwater Caves on Surface (X/Y: 320/224). This page will show you who has achieved the
                top of the rank list, and thus gotten a mythic item and 1 Trillion Gold + 100,000 XP and who has achieved tops in
                other ranks (Reward: 1 Legendary Enchanted Item, 2 Billion Gold and 10,000 XP).
            </p>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
        </div>

        <div id="rank-tops-info"></div>
    </div>
@endsection
