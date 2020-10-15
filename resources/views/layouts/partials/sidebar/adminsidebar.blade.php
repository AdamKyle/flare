<ul id="sidebarnav">
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="fas fa-users"></i><span class="hide-menu"> Manage Users</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('users.list')}}">Users</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-quill-ink"></i><span class="hide-menu">Manage Classes/Races</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('races.list')}}">Races</a></li>
            <li><a href="{{route('races.create')}}">Create Race</a></li>
            <li><a href="{{route('classes.list')}}">Classes</a></li>
            <li><a href="{{route('classes.create')}}">Create Class</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-scroll-unfurled"></i><span class="hide-menu">Manage Maps</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('maps')}}">Maps</a></li>
            <li><a href="{{route('maps.upload')}}">Upload Map</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-compass"></i><span class="hide-menu">Manage Locations</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('locations.list')}}">Locations</a></li>
            <li><a href="{{route('locations.create')}}">Create Location</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-trail"></i><span class="hide-menu">Manage Adventures</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('adventures.list')}}">Adventures</a></li>
            <li><a href="{{route('adventures.create')}}">Create Adventure</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-eye-monster"></i><span class="hide-menu">Manage Monsters</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('monsters.list')}}">Monsters</a></li>
            <li><a href="{{route('monsters.create')}}">Create Monster</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-sword"></i><span class="hide-menu">Manage Items</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('items.list')}}">Items</a></li>
            <li><a href="{{route('items.create')}}">Create Item</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-burning-embers"></i><span class="hide-menu">Manage Affixes</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('affixes.list')}}">Affixes</a></li>
            <li><a href="{{route('affixes.create')}}">Create Affix</a></li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-muscle-up"></i><span class="hide-menu">Manage Skills</span></a>
        <ul aria-expanded="false" class="collapse">
            <li><a href="{{route('skills.list')}}">Skills</a></li>
            <li><a href="{{route('skills.create')}}">Create Skill</a></li>
        </ul>
    </li>
</ul>
