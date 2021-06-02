@extends('layouts.app')

@section('content')
    <div class="container welcome">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-11 col-md-12">
                <div class="jumbotron  mt-5">
                    <h1>Planes of Tlessa</h1>
                    <p>A world full of mystery and exploration.</p>
                    <a class="btn btn-primary btn" href="{{route('register')}}" role="button">Join Today!</a>
                    <a class="btn btn-success btn" href="{{route('info.page', ['pageName' => 'home'])}}" role="button">Learn More</a>
                    <a class="btn btn-warning btn" href="{{route('releases.list')}}" role="button">Releases</a>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mb-2 mt-2">
            <div class="col-lg-12">
                <img src="{{asset('promotion/game.png')}}" class="w-100" />
            </div>
        </div>
        <div class="lg-padding">
            <div class="row justify-content-center">
                <div class="col-xl-7 col-lg-8">
                    <div class="snippit-section text-center">
                        <h2>Stay logged in!</h2>
                        <p>
                            There is no set it and forget it. This game requires you be engaged. Timers and such only last minutes at best, with attack and movement timers being set to seconds.
                        </p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center lg-padding">
                <div class="row">
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <div class="card game-feature-card">
                            <div class="card-body">
                                <div class="game-feature-icon">
                                    <i class="ra ra-player"></i>
                                </div>
                                <div class="game-feature">
                                    <h5>
                                        <a href="{{route('info.page', [
                                            'pageName' => 'equipment'
                                        ])}}">Equip your character!</a>
                                    </h5>
                                    <p>Buy/sell weapons, armor, rings, artifacts and more and out fit your character for the road ahead. Who knows what beasties you might find!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <div class="card game-feature-card">
                            <div class="card-body">
                                <div class="game-feature-icon">
                                    <i class="ra ra-player-king "></i>
                                </div>
                                <div class="game-feature">
                                    <h5>
                                        <a href="{{route('info.page', [
                                            'pageName' => 'kingdoms'
                                        ])}}">Rule Kingdoms!</a>
                                    </h5>
                                    <p>In a game where there are no resets, can your kingdom survive? Or will it be taken by those more powerful?</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6">
                        <div class="card game-feature-card">
                            <div class="card-body">
                                <div class="game-feature-icon">
                                    <i class="ra ra-trail"></i>
                                </div>
                                <div class="game-feature">
                                    <h5>
                                        <a href="{{route('info.page', [
                                            'pageName' => 'adventure',
                                        ])}}">Go on adventures!</a>
                                    </h5>
                                    <p>Travel to new locations and find out their mysteries by partaking in location based adventures!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row map-feature">
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 text-center snippit-section map-feature-text">
                    <h2>See where you're going!</h2>
                    <p>Adventure on a map by clicking the action buttons. Certian locations will have adventures and some you can set sail from, such as ports!</p>
                    <a href="{{route('info.page', [
                        'pageName' => 'map',
                    ])}}" class="btn btn-primary map-btn">Learn more</a>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <img src="{{asset('promotion/map.png')}}" class="w-100 move-image-down" />
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-xl-7 col-lg-8">
                <div class="snippit-section text-center">
                    <h2>
                        <span class="fa-stack">
                            <i class="far fa-credit-card fa-stack-1x"></i>
                            <i class="fas fa-ban fa-stack-2x text-danger"></i>
                        </span>
                        Put that credit card away!
                    </h2>
                    <p>This game is free. This game has one philosophy: You want it? Earn it! Every thing from the best gear, to the stongest kingdoms to abillity to travel from one plane to the next is all only attainable by playing the game.</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center lg-padding">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="card game-feature-card">
                        <div class="card-body">
                            <div class="game-feature-icon">
                                <i class="ra ra-anvil"></i>
                            </div>
                            <div class="game-feature">
                                <h5>
                                    <a href="{{route('info.page', [
                                        'pageName' => 'crafting',
                                    ])}}">Crafting is simple</a>
                                </h5>
                                <p>No need to gather. You can just start crafting! Find tomes to get xp bonuses!</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="card game-feature-card">
                        <div class="card-body">
                            <div class="game-feature-icon">
                                <i class="ra ra-forging"></i>
                            </div>
                            <div class="game-feature">
                                <h5>
                                    <a href="{{route('info.page', [
                                        'pageName' => 'enchanting',
                                    ])}}">Enchant Gear!</a>
                                </h5>
                                <p>All you need is to destroy an item with an affix on it for the recipe! How easy is that!</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-6">
                    <div class="card game-feature-card">
                        <div class="card-body">
                            <div class="game-feature-icon">
                                <i class="ra  ra-wooden-sign "></i>
                            </div>
                            <div class="game-feature">
                                <h5>
                                    <a href="{{route('info.page', [
                                        'pageName' => 'market-board',
                                    ])}}">Market Board</a>
                                </h5>
                                <p>Buy and sell from the market board. Craft and Enchant items for others and make a profit!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center" style="margin-bottom: 70px;">
            <div class="col-xl-7 col-lg-8">
                <div class="snippit-section">
                    <h2 class="text-center">
                        <i class="far fa-question-circle"></i>
                        FAQ
                    </h2>

                    <dl class="mt-3">
                        <dt>Are There Cash Shops?</dt>
                        <dd>
                            No, and there never will be. You cannot buy anything in this game, no weapons, gear, armor,
                            advantages, nothing. You want it, you will earn it.
                        </dd>
                        <dt>Are there Adds?</dt>
                        <dd>
                            No. There are no adds what so ever.
                        </dd>
                        <dt>Is it persistent?</dt>
                        <dd>
                            Yes. You can log out if you are in the middle of an adventure or are launching an attack
                            on another kingdom. Assuming you have the right settings enabled, you will be
                            emailed when the action is finished.
                        </dd>
                        <dt>Is it idle?</dt>
                        <dd>
                            No and yes. The majority of the game is not idle based, but aspects such as managing
                            your kingdom, or going on adventures are considered idle. Adventures can range from 10-60 minutes
                            in length and disable you from doing pretty much anything. You can log out and be emailed, when it's done.
                            Kingdoms are also idle based in the fact that it takes time to recruit, build and attack.
                        </dd>
                        <dt>Does it use energy systems?</dt>
                        <dd>
                            No. Tlessa uses what's called: <a href="/information/time-gates">Time Gates</a>. These apply to actions you do and time you out
                            from doing that action again for a matter of seconds or minutes. However, the goal of Tlessa is
                            not to keep you engaged, so for example you could: Fight, Craft, Move and then wait for their respective timers
                            to end before doing the same thing. In the aforementioned example: Killing a monster gates you a 10 second time
                            out before being able to kill the monster again, but being killed by said monster, gives you a 20 second time out before being able
                            to revive.
                        </dd>
                        <dt>Are they're factions/guilds/clans?</dt>
                        <dd>
                            No. In Tlessa, it's every person for them selves. There is no guild or clan system in Tlessa.
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
