<!-- Menu Bar -->
<aside class="menu-bar menu-sticky menu-hidden">
    <div class="menu-items">
        <a href="{{ route('home') }}" class="link" data-toggle="tooltip-menu">
            <span class="icon fas fa-home"></span>
            <span class="title">Home</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=admin]" data-toggle="tooltip-menu">
            <span class="icon fas fa-user-shield"></span>
            <span class="title">Admin</span>
        </a>
        <a href="#no-link" class="link" data-target="[data-menu=manage-game]" data-toggle="tooltip-menu">
            <span class="icon ra ra-player"></span>
            <span class="title">Manage Game</span>
        </a>
    </div>

    <!-- Character -->
    <div class="menu-detail" data-menu="admin">
        <div class="menu-detail-wrapper">
            <a href="{{ route('admin.statistics') }}">
                <span class="fas fa-chart-bar"></span>
                Statistics
            </a>
            <hr />
            <h6 class="uppercase">Character Reward Queue</h6>
            <a href="{{ route('admin.character-reward-queue') }}">
                <span class="fas fa-layer-group"></span>
                Reward Queues
            </a>
            <hr />
            <h6 class="uppercase">Monitoring</h6>
            <a href="{{ route('admin.monitoring.exploration') }}">
                <span class="fas fa-map-marked-alt"></span>
                Exploration
            </a>
            <a href="{{ route('admin.monitoring.faction-loyalty') }}">
                <span class="fas fa-handshake"></span>
                Faction Loyalty
            </a>
            <a href="{{ route('admin.monitoring.delve') }}">
                <span class="fas fa-dungeon"></span>
                Delve
            </a>
            <a href="{{ route('admin.monitoring.batch-crafting') }}">
                <span class="fas fa-hammer"></span>
                Batch Crafting
            </a>
            <a href="{{ route('admin.monitoring.logs') }}">
                <span class="fas fa-file-alt"></span>
                Logs
            </a>
            <hr />
            <hr />
            <h6 class="uppercase">Event Management</h6>
            <a href="{{ route('admin.events') }}">
                <span class="fas fa-list"></span>
                Event Scheduler
            </a>
            <hr />
            <h6 class="uppercase">Feedback</h6>
        </div>
    </div>

    <div class="menu-detail" data-menu="manage-game">
        <h6 class="uppercase">Races and Classes</h6>
        <a href="{{ route('races.create') }}">
            <span class="fas fa-plus"></span>
            Create New Race
        </a>

        <a href="{{ route('classes.create') }}">
            <span class="fas fa-plus"></span>
            Create New Class
        </a>

        <a href="{{ route('class-specials.create') }}">
            <span class="fas fa-plus"></span>
            Create New Class Special
        </a>
        <hr />
        <h6 class="uppercase">Maps</h6>
        <a href="{{ route('maps.upload') }}">
            <span class="fas fa-plus"></span>
            Upload New Map
        </a>
        <hr />
        <h6 class="uppercase">Locations</h6>
        <a href="{{ route('admin.locations.index') }}">
            <span class="fas fa-plus"></span>
            Create New Location
        </a>
        <hr />
        <h6 class="uppercase">Quests</h6>
        <a href="{{ route('admin.quests.index') }}">
            <span class="fas fa-plus"></span>
            Create New Quest
        </a>
        <hr />
        <h6 class="uppercase">NPC's</h6>
        <a href="{{ route('admin.npcs.index') }}">
            <span class="fas fa-plus"></span>
            Create New NPC
        </a>
        <hr />
        <h6 class="uppercase">Kingdoms</h6>
        <a href="{{ route('kingdoms.export') }}">
            <span class="fas fa-file-export"></span>
            Export Data
        </a>
        <a href="{{ route('kingdoms.import') }}">
            <span class="fas fa-file-import"></span>
            Import Data
        </a>
        <hr />
        <h6 class="uppercase">Monsters</h6>
        <a href="{{ route('admin.monsters.index') }}">
            <span class="fas fa-plus"></span>
            Create New Monster
        </a>
        <a href="{{ route('admin.monsters.export') }}">
            <span class="fas fa-file-export"></span>
            Export Data
        </a>
        <a href="{{ route('admin.monsters.index') }}">
            <span class="fas fa-file-import"></span>
            Import Data
        </a>
        <hr />
        <h6 class="uppercase">Items</h6>
        <a href="{{ route('admin.items.index') }}">
            <span class="fas fa-plus"></span>
            Create New Item
        </a>
        <a href="{{ route('admin.items.export') }}">
            <span class="fas fa-file-export"></span>
            Export Data
        </a>
        <a href="{{ route('admin.items.index') }}">
            <span class="fas fa-file-import"></span>
            Import Data
        </a>
        <hr />
        <h6 class="uppercase">Affixes</h6>
        <a href="{{ route('affixes.create') }}">
            <span class="fas fa-plus"></span>
            Create New Affix
        </a>
        <a href="{{ route('affixes.export') }}">
            <span class="fas fa-file-export"></span>
            Export Data
        </a>
        <a href="{{ route('affixes.import') }}">
            <span class="fas fa-file-import"></span>
            Import Data
        </a>
        <hr />
        <h6 class="uppercase">Skills</h6>
        <a href="{{ route('skills.create') }}">
            <span class="fas fa-plus"></span>
            Create New Skill
        </a>
        <a href="{{ route('skills.export') }}">
            <span class="fas fa-file-export"></span>
            Export Data
        </a>
        <a href="{{ route('skills.import') }}">
            <span class="fas fa-file-import"></span>
            Import Data
        </a>
        <hr />
        <h6 class="uppercase">Item Skills</h6>
        <a href="{{ route('admin.items-skills.create') }}">
            <span class="fas fa-plus"></span>
            Create New Skill
        </a>
        <a href="{{ route('admin.items-skills.export-data') }}">
            <span class="fas fa-file-export"></span>
            Export Data
        </a>
        <a href="{{ route('admin.items-skills.import-data') }}">
            <span class="fas fa-file-import"></span>
            Import Data
        </a>
        <hr />
        <h6 class="uppercase">Passives</h6>
        <a href="{{ route('passive.skills.create') }}">
            <span class="fas fa-plus"></span>
            Create New Passive
        </a>
        <a href="{{ route('passive.skills.export') }}">
            <span class="fas fa-file-export"></span>
            Export Data
        </a>
        <a href="{{ route('passive.skills.import') }}">
            <span class="fas fa-file-import"></span>
            Import Data
        </a>
    </div>
</aside>
