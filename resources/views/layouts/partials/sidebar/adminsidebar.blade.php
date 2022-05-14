<!-- Menu Bar -->
<aside class="menu-bar menu-sticky menu-hidden">
    <div class="menu-items">
        <a href="{{route('home')}}" class="link" data-toggle="tooltip-menu" data-tippy-content="Home">
            <span class="icon fas fa-home"></span>
            <span class="title">Home</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=admin]" data-toggle="tooltip-menu"
           data-tippy-content="Administration">
            <span class="icon fas fa-user-shield"></span>
            <span class="title">Admin</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=manage-game]" data-toggle="tooltip-menu"
           data-tippy-content="Manage Game">
            <span class="icon ra ra-player"></span>
            <span class="title">Manage Game</span>
        </a>
    </div>

    <!-- Character -->
    <div class="menu-detail" data-menu="admin">
        <div class="menu-detail-wrapper">
            <a href="{{route('users.list')}}">
                <span class="far fa-user"></span>
                User List
            </a>
            <a href="{{route('admin.statistics')}}">
                <span class="fas fa-chart-bar"></span>
                Statistics
            </a>
            <a href="{{route('admin.chat-logs')}}">
                <span class="fas fa-clipboard-list"></span>
                Chat Logs
            </a>
            <a href="{{route('admin.info-management')}}">
                <span class="fas fa-feather"></span>
                Information Management
            </a>
        </div>
    </div>

    <!-- Manage Game -->
    <div class="menu-detail" data-menu="manage-game">
        <div class="menu-detail-wrapper">
            <a href="#no-link" class="active" data-toggle="collapse" data-target="#game-option">
                <span class="collapse-indicator la la-arrow-circle-down"></span>
                Game Systems
            </a>
            <div id="game-option" class="collapse">
                <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-races">
                    <span class="collapse-indicator la la-arrow-circle-down"></span>
                    Manage Races
                </a>

                <div id="manage-races" class="collapse">
                    <a href="{{route('races.list')}}">
                        <span class="fas fa-list"></span>
                        Races
                    </a>
                    <a href="{{route('races.create')}}">
                        <span class="fas fa-plus"></span>
                        Create New Race
                    </a>
                </div>

                <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-classes">
                    <span class="collapse-indicator la la-arrow-circle-down"></span>
                    Manage Classes
                </a>

                <div id="manage-classes" class="collapse">
                    <a href="{{route('classes.list')}}">
                        <span class="fas fa-list"></span>
                        Classes
                    </a>
                    <a href="{{route('classes.create')}}">
                        <span class="fas fa-plus"></span>
                        Create New Class
                    </a>
                </div>

                <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-maps">
                    <span class="collapse-indicator la la-arrow-circle-down"></span>
                    Manage Maps
                </a>

                <div id="manage-maps" class="collapse">
                    <a href="{{route('maps')}}">
                        <span class="ra ra-scroll-unfurled"></span>
                        Maps
                    </a>
                    <a href="{{route('maps.upload')}}">
                        <span class="fas fa-plus"></span>
                        Upload New Map
                    </a>

                    <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-locations">
                        <span class="collapse-indicator la la-arrow-circle-down"></span>
                        Manage Locations
                    </a>

                    <div id="manage-locations" class="collapse">
                        <a href="{{route('locations.list')}}">
                            <span class="ra ra-compass"></span>
                            Locations
                        </a>
                        <a href="{{route('locations.create')}}">
                            <span class="fas fa-plus"></span>
                            Create New Location
                        </a>
                    </div>

                    <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-quests">
                        <span class="collapse-indicator la la-arrow-circle-down"></span>
                        Manage Quests
                    </a>

                    <div id="manage-quests" class="collapse">
                        <a href="{{route('quests.index')}}">
                            <span class="ra ra-book"></span>
                            Quests
                        </a>
                        <a href="{{route('quests.create')}}">
                            <span class="fas fa-plus"></span>
                            Create New Quest
                        </a>
                    </div>

                    <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-npcs">
                        <span class="collapse-indicator la la-arrow-circle-down"></span>
                        Manage NPCs
                    </a>

                    <div id="manage-npcs" class="collapse">
                        <a href="{{route('npcs.index')}}">
                            <span class="ra ra-pawn"></span>
                            Npcs
                        </a>
                        <a href="{{route('npcs.create')}}">
                            <span class="fas fa-plus"></span>
                            Create New NPC
                        </a>
                    </div>
                </div>
                <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-kingdoms">
                    <span class="collapse-indicator la la-arrow-circle-down"></span>
                    Manage Kingdoms
                </a>

                <div id="manage-kingdoms" class="collapse">
                    <a href="{{route('buildings.list')}}">
                        <span class="ra ra-guarded-tower"></span>
                        Buildings
                    </a>
                    <a href="{{route('units.list')}}">
                        <span class="ra ra-guarded-tower"></span>
                        Units
                    </a>
                    <a href="{{route('kingdoms.export')}}">
                        <span class="fas fa-file-export"></span>
                        Export Data
                    </a>
                    <a href="{{route('kingdoms.import')}}">
                        <span class="fas fa-file-import"></span>
                        Import Data
                    </a>
                </div>

                <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-monsters">
                    <span class="collapse-indicator la la-arrow-circle-down"></span>
                    Manage Monsters
                </a>

                <div id="manage-monsters" class="collapse">
                    <a href="{{route('monsters.list')}}">
                        <span class="ra ra-eye-monster"></span>
                        Monsters
                    </a>
                    <a href="{{route('monsters.create')}}">
                        <span class="fas fa-plus"></span>
                        Create New Monster
                    </a>
                    <a href="{{route('monsters.export')}}">
                        <span class="fas fa-file-export"></span>
                        Export Data
                    </a>
                    <a href="{{route('monsters.import')}}">
                        <span class="fas fa-file-import"></span>
                        Import Data
                    </a>
                </div>

                <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-items">
                    <span class="collapse-indicator la la-arrow-circle-down"></span>
                    Manage Items
                </a>

                <div id="manage-items" class="collapse">
                    <a href="{{route('items.list')}}">
                        <span class="ra ra-sword"></span>
                        Items
                    </a>
                    <a href="{{route('items.create')}}">
                        <span class="fas fa-plus"></span>
                        Create New Item
                    </a>
                    <a href="{{route('items.export')}}">
                        <span class="fas fa-file-export"></span>
                        Export Data
                    </a>
                    <a href="{{route('items.import')}}">
                        <span class="fas fa-file-import"></span>
                        Import Data
                    </a>

                    <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-affixes">
                        <span class="collapse-indicator la la-arrow-circle-down"></span>
                        Manage Affixes
                    </a>

                    <div id="manage-affixes" class="collapse">
                        <a href="{{route('affixes.list')}}">
                            <span class="ra ra-burning-embers"></span>
                            Affixes
                        </a>
                        <a href="{{route('affixes.create')}}">
                            <span class="fas fa-plus"></span>
                            Create New Affix
                        </a>
                        <a href="{{route('affixes.export')}}">
                            <span class="fas fa-file-export"></span>
                            Export Data
                        </a>
                        <a href="{{route('affixes.import')}}">
                            <span class="fas fa-file-import"></span>
                            Import Data
                        </a>
                    </div>
                </div>
                <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-skills">
                    <span class="collapse-indicator la la-arrow-circle-down"></span>
                    Manage Skills
                </a>

                <div id="manage-skills" class="collapse">
                    <a href="{{route('skills.list')}}">
                        <span class="ra ra-muscle-up"></span>
                        Skills
                    </a>
                    <a href="{{route('skills.create')}}">
                        <span class="fas fa-plus"></span>
                        Create New Skill
                    </a>
                    <a href="{{route('skills.export')}}">
                        <span class="fas fa-file-export"></span>
                        Export Data
                    </a>
                    <a href="{{route('skills.import')}}">
                        <span class="fas fa-file-import"></span>
                        Import Data
                    </a>
                </div>

                <a href="#no-link" class="active" data-toggle="collapse" data-target="#manage-passive-skills">
                    <span class="collapse-indicator la la-arrow-circle-down"></span>
                    Manage Passive Skills
                </a>

                <div id="manage-passive-skills" class="collapse">
                    <a href="{{route('passive.skills.list')}}">
                        <span class="ra ra-muscle-up"></span>
                        Passives
                    </a>
                    <a href="{{route('passive.skills.create')}}">
                        <span class="fas fa-plus"></span>
                        Create New Passive
                    </a>
                    <a href="{{route('passive.skills.export')}}">
                        <span class="fas fa-file-export"></span>
                        Export Data
                    </a>
                    <a href="{{route('passive.skills.import')}}">
                        <span class="fas fa-file-import"></span>
                        Import Data
                    </a>
                </div>
            </div>
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
