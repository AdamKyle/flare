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
    <li><a href="{{route('info.page', [
                'pageName' => 'character-information'
              ])}}">Character Information</a></li>
    <li class="nav-devider"></li>
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
            'pageName' => 'skill-information'
          ])}}">Skill Information</a>
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
