<ul id="sidebarnav">
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
</ul>
