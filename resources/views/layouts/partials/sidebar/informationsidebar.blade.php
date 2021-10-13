<ul id="sidebarnav">
    <li class="nav-small-cap">Basic Information</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="fas fa-question-circle"></i><span class="hide-menu">Basic Information</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('info.page', ['pageName' => 'home'])}}">Home</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'rules'])}}">Core Rules</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'chat-commands'])}}">Chat Commands</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'settings'])}}"><i class="fas fa-user-cog"></i> Player Settings</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'notifications'])}}"><i class="fas fa-bell"></i> Notifications</a></li>
        </ul>
    </li>
    <li class="nav-small-cap">Character Information</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-player"></i><span class="hide-menu">Character Information</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('info.page', ['pageName' => 'races-and-classes'])}}">Race and Class</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'character-stats'])}}">Stats</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'skill-information'])}}">Skills</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'equipment'])}}">Equipment</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'equipment-sets'])}}">Equipment Sets</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'currencies'])}}">Currencies</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'combat'])}}">Combat</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'voidance'])}}">Voidance / Devoidance</a></li>
        </ul>
    </li>
    <li class="nav-small-cap">Map</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-scroll-unfurled"></i><span class="hide-menu">Map</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('info.page', ['pageName' => 'movement'])}}">Movement</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'locations'])}}">Locations</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'adventure'])}}">Adventuring</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'set-sail'])}}">Setting Sail</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'teleport'])}}">Teleporting</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'traverse'])}}">Traverse</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'planes'])}}">Planes</a></li>
        </ul>
    </li>
    <li class="nav-small-cap">Kingdoms</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-guarded-tower"></i><span class="hide-menu">Kingdoms</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('info.page', ['pageName' => 'kingdoms'])}}">Kingdoms</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'attacking-kingdoms'])}}">Attacking A Kingdom</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'items-and-kingdoms'])}}">Items and Kingdoms</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'npc-kingdoms'])}}">NPC Kingdom</a></li>
        </ul>
    </li>
    <li class="nav-small-cap">Npc's and Quests</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-pawn"></i><span class="hide-menu">NPC's & Quests</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('info.page', ['pageName' => 'npcs'])}}">NPCs</a></li>
            <li><a href="{{route('info.page', ['pageName' => 'quests'])}}">Quests</a></li>
        </ul>
    </li>
    <li class="nav-small-cap">Market</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="fas fa-sign"></i><span class="hide-menu">Market</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('info.page', ['pageName' => 'market-board'])}}">Market Board</a></li>
        </ul>
    </li>
    <li class="nav-small-cap">Crafting/Enchanting</li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-anvil"></i><span class="hide-menu">Crafting/Enchanting</span></a>
        <ul aria-expanded="false" class="collapse">
            <li>
                <a href="{{route('info.page', ['pageName' => 'crafting'])}}">Crafting</a>
            </li>
            <li>
                <a href="{{route('info.page', ['pageName' => 'enchanting'])}}">Enchanting</a>
            </li>
            <li>
                <a href="{{route('info.page', ['pageName' => 'disenchanting'])}}">Disenchanting</a>
            </li>
            <li>
                <a href="{{route('info.page', ['pageName' => 'usable-items'])}}">Usable Items (Alchemy)</a>
            </li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-eye-monster"></i><span class="hide-menu">Lists</span></a>
        <ul aria-expanded="false" class="collapse">
            <li>
                <a href="{{route('info.page', ['pageName' => 'celestials'])}}">Celestial List</a>
            </li>
            <li>
                <a href="{{route('info.page', ['pageName' => 'monsters'])}}">Monsters List</a>
            </li>
            <li>
                <a href="{{route('info.page', ['pageName' => 'quest-items'])}}">Quest Item List</a>
            </li>
        </ul>
    </li>
</ul>
