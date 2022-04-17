<!-- Menu Bar -->
<aside class="menu-bar menu-sticky menu-hidden">
    <div class="menu-items">
        <a href="{{route('game')}}" class="link" data-toggle="tooltip-menu" data-tippy-content="Game">
            <span class="icon fas fa-dice-d20"></span>
            <span class="title">Game</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=shop]" data-toggle="tooltip-menu"
           data-tippy-content="Shop">
            <span class="icon ra ra-anvil"></span>
            <span class="title">Shop</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=market]" data-toggle="tooltip-menu" data-tippy-content="Market">
            <span class="icon ra ra-wooden-sign"></span>
            <span class="title">Market</span>
        </a>
    </div>

    <!-- Character -->
    <div class="menu-detail" data-menu="character">
        <div class="menu-detail-wrapper">
            <a href="{{route('game.character.sheet')}}">
                <span class="ra ra-player"></span>
                Character Sheet
            </a>
        </div>
    </div>

    <!-- Shop -->
    <div class="menu-detail" data-menu="shop">
        <div class="menu-detail-wrapper">
            <a href="{{route('game.shop.buy', ['character' => auth()->user()->character->id])}}">
                <span class="fas fa-cart-plus"></span>
                Buy
            </a>
            <a href="{{route('game.shop.sell', ['character' => auth()->user()->character->id])}}">
                <span class="fas fa-dollar-sign"></span>
                Sell
            </a>
        </div>
    </div>

    <!-- Market -->
    <div class="menu-detail" data-menu="market">
        <div class="menu-detail-wrapper">
            <a href="{{route('game.market')}}">
                <span class="fas fa-file-invoice-dollar"></span>
                Visit Market
            </a>
            <a href="{{route('game.market.sell')}}">
                <span class="fas fa-hand-holding-usd"></span>
                Sell Items
            </a>
            <a href="{{route('game.current-listings', [
                'character' => auth()->user()->character->id
            ])}}">
                <span class="fas fa-search-dollar"></span>
                Your Listings
            </a>
        </div>
    </div>
</aside>

@push('scripts')
    <script>
        const menuBar = document.querySelector(".menu-bar");

        menuBar.classList.add("menu-hidden");

        document.documentElement.classList.add("menu-hidden");

        menuBar.querySelectorAll(".menu-detail.open").forEach((menuDetail) => {
            hideOverlay();

            if (!menuBar.classList.contains("menu-wide")) {
                menuDetail.classList.remove("open");
            }
        });
    </script>
@endpush
