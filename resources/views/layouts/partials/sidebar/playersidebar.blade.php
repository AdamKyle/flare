<ul id="sidebarnav">
    <li>
        <a href="{{route('game')}}" aria-expanded="false"><i class="fas fa-gamepad"></i><span class="hide-menu ml-2">Game</span></a>
    </li>
    <li class="nav-devider"></li>
    <li class="nav-small-cap">Character Management</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-player"></i><span class="hide-menu">Character</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('game.character.sheet')}}">Character Sheet</a></li>
        </ul>
    </li>
    <li class="nav-devider"></li>
    <li class="nav-small-cap">Manage Kingdoms</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-guarded-tower"></i><span class="hide-menu">Kingdoms</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('game.kingdom.attack-logs', ['character' => auth()->user()->character->id])}}">Attack Logs</a></li>
            <li><a href="{{route('game.kingdom.unit-movement', ['character' => auth()->user()->character->id])}}">Unit Movement</a></li>
        </ul>
    </li>
    <li class="nav-devider"></li>
    <li class="nav-small-cap">Manage Adventures</li>
    <li id="adventure-menu">
        {{-- The adventure menu is built via a react component. --}}
    </li>
    <li class="nav-devider"></li>
    <li class="nav-small-cap">Buy/Sell Items</li>
    <li>
        <a class="has-arrow " href="#" aria-expanded="false"><i class="ra ra-anvil"></i><span class="hide-menu">Shop</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href={{route('game.shop.buy', ['character' => auth()->user()->character->id])}}>Buy</a></li>
            <li><a href={{route('game.shop.sell', ['character' => auth()->user()->character->id])}}>Sell</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow " href="#" aria-expanded="false"><i class="ra ra-wooden-sign"></i><span class="hide-menu">Market</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('game.market')}}">Visit the market</a></li>
            <li><a href="{{route('game.market.sell')}}">Sell items</a></li>
            <li><a href="{{route('game.current-listings', [
                'character' => auth()->user()->character->id
            ])}}">My Listings</a></li>
        </ul>
    </li>
</ul>
