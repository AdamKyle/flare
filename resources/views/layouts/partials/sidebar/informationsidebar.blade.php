<ul id="sidebarnav">
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'home'
          ])}}" aria-expanded="false">
          <i class="fas fa-question-circle"></i><span class="hide-menu ml-2">Information</span>
        </a>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'rules'
        ])}}">Core Rules</a>
    </li>
    <li class="nav-devider"></li>
    <li>
      <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-player"></i><span class="hide-menu">Character Information</span></a>
      <ul aria-expanded="false" class="collapse">
          <li><a href="{{route('info.page', ['pageName' => 'races-and-classes'])}}">Race and Class</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'skill-information'])}}">Skills</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'character-stats'])}}">Stats</a></li>
      </ul>
    </li>
    <li class="nav-devider"></li>
    <li>
      <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-scroll-unfurled"></i><span class="hide-menu">Map</span></a>
      <ul aria-expanded="false" class="collapse">
          <li><a href="{{route('info.page', ['pageName' => 'movement'])}}">Movement</a></li>
          <li><a href="form-basic.html">Locations</a></li>
          <li><a href="form-basic.html">Adventuring</a></li>
          <li><a href="form-basic.html">Setting Sail</a></li>
          <li><a href="form-basic.html">Teleporting</a></li>
      </ul>
    </li>
    <li><a href="{{route('info.page', [
                'pageName' => 'map'
              ])}}">Map</a></li>
    <li class="nav-devider"></li>
    <li><a href="{{route('info.page', [
        'pageName' => 'adventure'
      ])}}">Adventure</a></li>
    <li class="nav-devider"></li>
    <li>
      <a href="{{route('info.page', [
          'pageName' => 'notifications'
        ])}}">Notifications</a>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'crafting'
          ])}}">Crafting</a>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'enchanting'
          ])}}">Enchanting</a>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'monsters'
          ])}}">Monsters List</a>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'time-gates'
          ])}}">Time Gates</a>
    </li>
</ul>
