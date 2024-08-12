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
            <a href="{{route('admin.guide-quests')}}">
                <span class="fas fa-feather"></span>
                Guide Quests
            </a>
            <hr />
            <h6 class="uppercase">Event Management</h6>
            <a href="{{route('admin.raids.list')}}">
                <span class="fas fa-list"></span>
                Raid Events
            </a>
            <a href="{{route('admin.events')}}">
                <span class="fas fa-list"></span>
                Event Scheduler
            </a>
            <hr />
            <h6 class="uppercase">Feedback</h6>
            <a href="{{route('admin.feedback.bugs')}}">
                <span class="fas fa-bug"></span>
                Bugs
            </a>
            <a href="{{route('admin.feedback.suggestions')}}">
                <span class="far fa-lightbulb"></span>
                Suggestions
            </a>

        </div>
    </div>

    <div class="menu-detail" data-menu="manage-game">
        <h6 class="uppercase">Races and Classes</h6>
        <a href="{{route('races.list')}}">
            <span class="fas fa-list"></span>
            Races
        </a>
        <a href="{{route('races.create')}}">
            <span class="fas fa-plus"></span>
            Create New Race
        </a>

        <a href="{{route('classes.list')}}">
            <span class="fas fa-list"></span>
            Classes
        </a>
        <a href="{{route('classes.create')}}">
            <span class="fas fa-plus"></span>
            Create New Class
        </a>

        <a href="{{route('class-specials.list')}}">
            <span class="fas fa-list"></span>
            Class Specials
        </a>
        <a href="{{route('class-specials.create')}}">
            <span class="fas fa-plus"></span>
            Create New Class Special
        </a>
        <hr />
        <h6 class="uppercase">Maps</h6>
        <a href="{{route('maps')}}">
            <span class="ra ra-scroll-unfurled"></span>
            Maps
        </a>
        <a href="{{route('maps.upload')}}">
            <span class="fas fa-plus"></span>
            Upload New Map
        </a>
        <hr />
        <h6 class="uppercase">Locations</h6>
        <a href="{{route('locations.list')}}">
            <span class="ra ra-compass"></span>
            Locations
        </a>
        <a href="{{route('locations.create')}}">
            <span class="fas fa-plus"></span>
            Create New Location
        </a>
        <hr />
        <h6 class="uppercase">Quests</h6>
        <a href="{{route('quests.index')}}">
            <span class="ra ra-book"></span>
            Quests
        </a>
        <a href="{{route('quests.create')}}">
            <span class="fas fa-plus"></span>
            Create New Quest
        </a>
        <hr />
        <h6 class="uppercase">NPC's</h6>
        <a href="{{route('npcs.index')}}">
            <span class="ra ra-pawn"></span>
            Npcs
        </a>
        <a href="{{route('npcs.create')}}">
            <span class="fas fa-plus"></span>
            Create New NPC
        </a>
        <hr />
        <h6 class="uppercase">Kingdoms</h6>
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
        <hr />
        <h6 class="uppercase">Monsters</h6>
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
        <hr />
        <h6 class="uppercase">Items</h6>
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
        <hr />
        <h6 class="uppercase">Affixes</h6>
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
        <hr />
        <h6 class="uppercase">Skills</h6>
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
        <hr />
        <h6 class="uppercase">Item Skills</h6>
        <a href="{{route('admin.items-skills.list')}}">
            <span class="ra ra-player-thunder-struck"></span>
            Item Skills
        </a>
        <a href="{{route('admin.items-skills.create')}}">
            <span class="fas fa-plus"></span>
            Create New Skill
        </a>
        <a href="{{route('admin.items-skills.export-data')}}">
            <span class="fas fa-file-export"></span>
            Export Data
        </a>
        <a href="{{route('admin.items-skills.import-data')}}">
            <span class="fas fa-file-import"></span>
            Import Data
        </a>
        <hr />
        <h6 class="uppercase">Passives</h6>
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

</aside>
