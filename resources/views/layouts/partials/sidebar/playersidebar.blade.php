<!-- Menu Bar -->
<aside class="menu-bar menu-sticky menu-hidden">
    <div class="menu-items">
        <a href="{{ route('game') }}" class="link" data-toggle="tooltip-menu" data-tippy-content="Game">
            <span class="icon fas fa-dice-d20"></span>
            <span class="title">Game</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=shop]" data-toggle="tooltip-menu"
            data-tippy-content="Shop">
            <span class="icon ra ra-anvil"></span>
            <span class="title">Shop</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=market]" data-toggle="tooltip-menu"
            data-tippy-content="Market">
            <span class="icon ra ra-wooden-sign"></span>
            <span class="title">Market</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=quests]" data-toggle="tooltip-menu"
            data-tippy-content="Quests">
            <span class="icon fas fa-feather"></span>
            <span class="title">Quest Log</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=tops]" data-toggle="tooltip-menu"
            data-tippy-content="Tops">
            <span class="icon fas fa-chart-area"></span>
            <span class="title">Tops</span>
        </a>
        <a href="{{ route('game.event.calendar') }}" class="link" data-toggle="tooltip-menu"
            data-tippy-content="Events">
            <span class="icon far fa-calendar-alt"></span>
            <span class="title">Event Calendar</span>
        </a>
    </div>

    <!-- Character -->
    <div class="menu-detail" data-menu="character">
        <div class="menu-detail-wrapper">
            <a href="{{ route('game.character.sheet') }}">
                <span class="ra ra-player"></span>
                Character Sheet
            </a>
        </div>
    </div>

    <!-- Shop -->
    <div class="menu-detail" data-menu="shop">
        <div class="menu-detail-wrapper">
            <h6 class="uppercase">General Shop (Uses Gold)</h6>
            <a href="{{ route('game.shop.buy', ['character' => auth()->user()->character->id]) }}">
                <span class="fas fa-cart-plus"></span>
                Buy
            </a>
            <a href="{{ route('game.shop.sell', ['character' => auth()->user()->character->id]) }}">
                <span class="fas fa-dollar-sign"></span>
                Sell
            </a>
        </div>
        <hr />
        <div class="menu-detail-wrapper">
            <h6 class="uppercase">Goblin Shop (Uses Gold Bars)</h6>
            <a href="{{ route('game.goblin-shop.shop', ['character' => auth()->user()->character->id]) }}">
                <span class="fas fa-cart-plus"></span>
                Buy
            </a>
        </div>
        <hr />
    </div>

    <!-- Market -->
    <div class="menu-detail" data-menu="market">
        <div class="menu-detail-wrapper">
            <a href="{{ route('game.market') }}">
                <span class="fas fa-file-invoice-dollar"></span>
                Visit Market
            </a>
            <a
                href="{{ route('game.current-listings', [
                    'character' => auth()->user()->character->id,
                ]) }}">
                <span class="fas fa-search-dollar"></span>
                Your Listings
            </a>
        </div>
    </div>

    <!-- Quests -->
    <div class="menu-detail" data-menu="quests">
        <div class="menu-detail-wrapper">
            <a href="{{ route('completed.quests', ['user' => auth()->user()]) }}">
                <span class="fas fa-feather"></span>
                Completed Quests
            </a>
            @if (GuideQuests::canSeeGuideQuestsLog())
                <a href="{{ route('completed.guide-quests', ['user' => auth()->user()]) }}">
                    <span class="fas fa-feather"></span>
                    Completed Guide Quests
                </a>
            @endif
        </div>
    </div>

    <div class="menu-detail" data-menu="tops">
        <div class="menu-detail-wrapper">
            <a href="{{ route('game.tops') }}">
                <span class="fas fa-list"></span>
                Characters
            </a>
            <a href="{{ route('game.ranked-fights-tops') }}">
                <span class="fas fa-list"></span>
                Ranked Fights Tops
            </a>
        </div>
    </div>
</aside>
