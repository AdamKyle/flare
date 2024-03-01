<!-- Menu Bar -->
<aside class="menu-bar menu-sticky">
    <div class="menu-items">
        <a href="{{ route('info.page', ['pageName' => 'home']) }}" class="link" data-toggle="tooltip-menu"
            data-tippy-content="Home">
            <span class="icon la la-book-open"></span>
            <span class="title">Home</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=basic-info]" data-toggle="tooltip-menu"
            data-tippy-content="Basic Info">
            <span class="icon fas fa-question-circle"></span>
            <span class="title">Basic Information</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=character-info]" data-toggle="tooltip-menu"
            data-tippy-content="Character Info">
            <span class="icon ra ra-muscle-fat"></span>
            <span class="title">Character Information</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=map]" data-toggle="tooltip-menu"
            data-tippy-content="Map">
            <span class="icon ra ra-scroll-unfurled"></span>
            <span class="title">Map</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=kingdom]" data-toggle="tooltip-menu"
            data-tippy-content="Kingdom">
            <span class="icon ra ra-tower"></span>
            <span class="title">Kingdoms</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=game-systems]" data-toggle="tooltip-menu"
            data-tippy-content="Game Systems">
            <span class="icon ra ra-tower"></span>
            <span class="title">Game Systems</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=gear-sets]" data-toggle="tooltip-menu"
            data-tippy-content="Gear Sets">
            <span class="icon ra ra-knight-helmet"></span>
            <span class="title">Gear Sets</span>
        </a>
    </div>


    <!-- Basic Information -->
    <div class="menu-detail" data-menu="basic-info">
        <div class="menu-detail-wrapper">
            <h6 class="uppercase">Core</h6>
            <a href="{{ route('info.page', ['pageName' => 'rules']) }}">
                <span class="text-sm fas fa-info-circle"></span>
                Rules
            </a>
            <a href="{{ route('info.page', ['pageName' => 'some-clicking-required']) }}">
                <span class="fas fa-mouse-pointer"></span>
                Some clicking Required
            </a>
            <a href="{{ route('info.page', ['pageName' => 'settings']) }}">
                <span class="text-sm fas fa-cogs"></span>
                Player Settings
            </a>
            <a href="{{ route('info.page', ['pageName' => 'chat-commands']) }}">
                <span class="text-sm far fa-comment-dots"></span>
                Chat Commands
            </a>
            <a href="{{ route('info.page', ['pageName' => 'events']) }}">
                <span class="text-sm fas fa-calendar"></span>
                Events
            </a>
            <a href="{{ route('info.page', ['pageName' => 'raids']) }}">
                <span class="text-sm ra ra-monster-skull"></span>
                Raids
            </a>
        </div>
        <hr />
        <div class="menu-detail-wrapper">
            <h6 class="uppercase">Shops</h6>
            <a href="{{ route('info.page', ['pageName' => 'shop']) }}">
                <span class="text-sm icon ra ra-anvil"></span>
                Shop
            </a>
            <a href="{{ route('info.page', ['pageName' => 'goblin-shop']) }}">
                <span class="text-sm icon ra ra-anvil"></span>
                Goblin Shop
            </a>
            <a href="{{ route('info.page', ['pageName' => 'market-board']) }}">
                <span class="ra ra-wooden-sign"></span>
                Market Board
            </a>
        </div>
        <hr />
        <div class="menu-detail-wrapper">
            <h6 class="uppercase">Cosmetic Features</h6>
            <a href="{{ route('info.page', ['pageName' => 'cosmetic-text']) }}">
                <span class="text-sm icon far fa-keyboard"></span>
                Cosmetic Text
            </a>
        </div>
    </div>

    <!-- Character -->
    <div class="menu-detail" data-menu="character-info">
        <div class="menu-detail-wrapper">
            <a href="{{ route('info.page', ['pageName' => 'races-and-classes']) }}">
                <span class="ra ra-player"></span>
                Races and Classes
            </a>
            <a href="{{ route('info.page', ['pageName' => 'class-ranks']) }}">
                <span class="fas fa-users"></span>
                Class ranks
            </a>
            <a href="{{ route('info.page', ['pageName' => 'character-stats']) }}">
                <span class="far fa-chart-bar"></span>
                Character Stats
            </a>
            <a href="{{ route('info.page', ['pageName' => 'reincarnation']) }}">
                <span class="ra ra-player-pyromaniac"></span>
                Reincarnation
            </a>
            <a href="{{ route('info.page', ['pageName' => 'character-xp']) }}">
                <i class="fas fa-chart-line"></i>
                Character XP
            </a>
            <hr />
            <h6 class="uppercase">Skills</h6>
            <a href="{{ route('info.page', ['pageName' => 'skill-information']) }}">
                <span class="ra ra-aura"></span>
                Skills
            </a>
            <a href="{{ route('info.page', ['pageName' => 'class-skills']) }}">
                <span class="ra ra-player-pyromaniac"></span>
                Class Skills
            </a>
            <hr>
            <h6 class="uppercase">Equipment</h6>
            <a href="{{ route('info.page', ['pageName' => 'equipment']) }}">
                <span class="ra ra-axe"></span>
                Equipment
            </a>
            <a href="{{ route('info.page', ['pageName' => 'gear-progression']) }}">
                <span class="fas fa-level-up-alt"></span>
                Gear Progression
            </a>
            <a href="{{ route('info.page', ['pageName' => 'equipment-sets']) }}">
                <span class="ra ra-battered-axe"></span>
                Equipment Sets
            </a>
            <hr />
            <h6 class="uppercase">Misc.</h6>
            <a href="{{ route('info.page', ['pageName' => 'currencies']) }}">
                <span class="fas fa-coins"></span>
                Currencies
            </a>
            <a href="{{ route('info.page', ['pageName' => 'combat']) }}">
                <span class="ra ra-archer"></span>
                Combat
            </a>
            <a href="{{ route('info.page', ['pageName' => 'ambush-and-counter']) }}">
                <span class="ra ra-muscle-fat"></span>
                Ambush and Counter
            </a>
            <a href="{{ route('info.page', ['pageName' => 'ranked-fights']) }}">
                <span class="ra ra-monster-skull"></span>
                Ranked Fights (End Game)
            </a>
            <a href="{{ route('info.page', ['pageName' => 'player-vs-player']) }}">
                <span class="ra ra-helmet"></span>
                Player vs Player
            </a>
            <a href="{{ route('info.page', ['pageName' => 'monthly-pvp-event']) }}">
                <span class="ra ra-helmet"></span>
                Monthly Pvp Event
            </a>
            <a href="{{ route('info.page', ['pageName' => 'automation']) }}">
                <span class="fas fa-user-clock"></span>
                Automation
            </a>
            <a href="{{ route('info.page', ['pageName' => 'voidance']) }}">
                <span class="ra ra-burning-book"></span>
                Voidance/Devoidance
            </a>
        </div>
    </div>

    <!-- Map -->
    <div class="menu-detail" data-menu="map">
        <div class="menu-detail-wrapper">
            <a href="{{ route('info.page', ['pageName' => 'planes']) }}">
                <span class="fas fa-layer-group"></span>
                Planes
            </a>
            <a href="{{ route('info.page', ['pageName' => 'exploration']) }}">
                <span class="fas fa-map-signs"></span>
                Exploration
            </a>
            <hr />
            <h6 class="uppercase">Map Movement</h6>
            <a href="{{ route('info.page', ['pageName' => 'movement']) }}">
                <span class="far fa-compass"></span>
                Movement Actions
            </a>
            <a href="{{ route('info.page', ['pageName' => 'traverse']) }}">
                <span class="ra ra-player-pyromaniac"></span>
                Traverse Action
            </a>
            <a href="{{ route('info.page', ['pageName' => 'set-sail']) }}">
                <span class="fas fa-ship"></span>
                Set Sail Action
            </a>
            <a href="{{ route('info.page', ['pageName' => 'locations']) }}">
                <span class="fas fa-search-location"></span>
                Map Locations
            </a>
            <a href="{{ route('info.page', ['pageName' => 'special-locations']) }}">
                <span class="fas fa-dungeon"></span>
                Special Locations
            </a>
            <hr />
            <h6 class="uppercase">Factions</h6>
            <a href="{{ route('info.page', ['pageName' => 'factions']) }}">
                <span class="ra ra-arrow-cluster"></span>
                Factions
            </a>
            <a href="{{ route('info.page', ['pageName' => 'faction-loyalty']) }}">
                <span class="ra ra-double-team"></span>
                Factions Loyalty
            </a>
        </div>
    </div>

    <!-- Kingdom -->
    <div class="menu-detail" data-menu="kingdom">
        <div class="menu-detail-wrapper">
            <a href="{{ route('info.page', ['pageName' => 'kingdoms']) }}">
                <span class="ra ra-guarded-tower"></span>
                Kingdoms
            </a>
            <a href="{{ route('info.page', ['pageName' => 'attacking-kingdoms']) }}">
                <span class="ra ra-daggers"></span>
                Attacking a kingdom
            </a>
            <a href="{{ route('info.page', ['pageName' => 'items-and-kingdoms']) }}">
                <span class="fas fa-flask"></span>
                Using items on kingdoms
            </a>
            <hr>
            <a href="{{ route('info.page', ['pageName' => 'npc-kingdoms']) }}">
                <span class="ra ra-player"></span>
                NPC Kingdoms
            </a>
            <a href="{{ route('info.page', ['pageName' => 'kingdom-passive-skills']) }}">
                <span class="fas fa-sitemap"></span>
                Kingdom Passive Skills
            </a>
        </div>
    </div>

    <!-- Game Systems -->
    <div class="menu-detail" data-menu="game-systems">
        <div class="menu-detail-wrapper">
            <h6 class="uppercase">NPC's and Quests</h6>
            <a href="{{ route('info.page', ['pageName' => 'npcs']) }}">
                <span class="ra ra-player"></span>
                NPC's
            </a>
            <a href="{{ route('info.page', ['pageName' => 'quests']) }}">
                <span class="ra ra-trophy"></span>
                Quests
            </a>
            <a href="{{ route('info.page', ['pageName' => 'guide-quests']) }}">
                <span class="ra ra-book"></span>
                Guide Quests
            </a>
            <hr />
            <h6 class="uppercase">Gambling and Mercenaries</h6>
            <a href="{{ route('info.page', ['pageName' => 'mercenary']) }}">
                <span class="ra ra-double-team"></span>
                Mercenaries
            </a>
            <a href="{{ route('info.page', ['pageName' => 'slots']) }}">
                <span class="ra ra-dice-two"></span>
                Slots
            </a>
            <hr />
            <h6 class="uppercase">Crafting and Enchanting</h6>
            <a href="{{ route('info.page', ['pageName' => 'crafting']) }}">
                <span class="ra ra-hammer"></span>
                Crafting
            </a>
            <a href="{{ route('info.page', ['pageName' => 'enchanting']) }}">
                <span class="ra ra-burning-book"></span>
                Enchanting
            </a>
            <a href="{{ route('info.page', ['pageName' => 'disenchanting']) }}">
                <span class="ra ra-explosion"></span>
                Disenchanting
            </a>
            <a href="{{ route('info.page', ['pageName' => 'alchemy']) }}">
                <span class="ra ra-round-bottom-flask"></span>
                Alchemy (Usable items)
            </a>
            <a href="{{ route('info.page', ['pageName' => 'random-enchants']) }}">
                <span class="ra ra-fairy-wand"></span>
                Random Enchantments (Uniques)
            </a>
            <a href="{{ route('info.page', ['pageName' => 'holy-items']) }}">
                <span class="fas fa-cross"></span>
                Holy Items
            </a>
            <a href="{{ route('info.page', ['pageName' => 'trinketry']) }}">
                <span class="ra ra-fire-shield"></span>
                Trinkets
            </a>
            <a href="{{ route('info.page', ['pageName' => 'gems']) }}">
                <span class="fas fa-gem"></span>
                Gem Crafting
            </a>
            <a href="{{ route('info.page', ['pageName' => 'seer-camp']) }}">
                <span class="fas fa-campground"></span>
                Seer Camp
            </a>
            <hr />
            <h6>Misc. Lists</h6>
            <a href="{{ route('info.page', ['pageName' => 'monsters']) }}">
                <span class="ra ra-broken-skull"></span>
                Monsters
            </a>
            <a href="{{ route('info.page', ['pageName' => 'celestials']) }}">
                <span class="ra ra-batwings"></span>
                Celestials
            </a>

            <a href="{{ route('info.page', ['pageName' => 'quest-items']) }}">
                <span class="ra ra-bat-sword"></span>
                Quest Items
            </a>
        </div>
    </div>

    <!-- Gear Sets -->
    <div class="menu-detail" data-menu="gear-sets">
        <div class="menu-detail-wrapper">
            <a href="{{ route('info.page', ['pageName' => 'mythical-items']) }}">
                <span class="ra ra-chain"></span>
                Mythics
            </a>
            <a href="{{ route('info.page', ['pageName' => 'hell-forged-set']) }}">
                <span class="ra ra-axe"></span>
                Hell Forged
            </a>
            <a href="{{ route('info.page', ['pageName' => 'purgatory-chains-set']) }}">
                <span class="ra ra-lightning-sword"></span>
                Purgatory Chains
            </a>
            <a href="{{ route('info.page', ['pageName' => 'pirate-lord-leather-set']) }}">
                <span class="ra ra-dervish-swords"></span>
                Pirate Lord Leather
            </a>
            <a href="{{ route('info.page', ['pageName' => 'corrupted-ice']) }}">
                <span class="ra ra-kaleidoscope"></span>
                Corrupted Ice
            </a>
            <a href="{{ route('info.page', ['pageName' => 'ancestral-items']) }}">
                <span class="ra ra-crowned-heart"></span>
                Ancestral Items
            </a>
        </div>
    </div>
</aside>
