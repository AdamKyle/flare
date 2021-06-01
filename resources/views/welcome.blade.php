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
                                        ])}}">Rule Kingdoms</a>
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
                                <i class="ra ra-scroll-unfurled"></i>
                            </div>
                            <div class="game-feature">
                                <h5>
                                    <a href="#">Quest items!</a>
                                </h5>
                                <p>Quest items let you travel on water and so much more! They are even easy to complete!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
