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
            'pageName' => 'time-gates'
          ])}}"><i class="far fa-clock"></i> Time Gates</a>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'rules'
        ])}}"><i class="fas fa-smoking-ban"></i> Core Rules</a>
    </li>
    <li class="nav-devider"></li>
    <li>
      <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-player"></i><span class="hide-menu">Character Information</span></a>
      <ul aria-expanded="false" class="collapse">
          <li><a href="{{route('info.page', ['pageName' => 'races-and-classes'])}}">Race and Class</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'character-stats'])}}">Stats</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'skill-information'])}}">Skills</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'equipment'])}}">Equipment</a></li>
      </ul>
    </li>
    <li class="nav-devider"></li>
    <li>
      <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-scroll-unfurled"></i><span class="hide-menu">Map</span></a>
      <ul aria-expanded="false" class="collapse">
          <li><a href="{{route('info.page', ['pageName' => 'movement'])}}">Movement</a></li>
          <li><a href="{{route('info.page', [
            'pageName' => 'locations'
          ])}}">Locations</a></li>
          <li><a href="{{route('info.page', [
            'pageName' => 'adventure'
          ])}}">Adventuring</a></li>
          <li><a href={{route('info.page', ['pageName' => 'set-sail'])}}>Setting Sail</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'teleport'])}}">Teleporting</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'traverse'])}}">Traverse</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'planes'])}}">Planes</a></li>
      </ul>
    </li>
    <li class="nav-devider"></li>
    <li>
      <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-guarded-tower"></i><span class="hide-menu">Kingdoms</span></a>
      <ul aria-expanded="false" class="collapse">
          <li><a href="{{route('info.page', ['pageName' => 'kingdoms'])}}">Kingdoms</a></li>
          <li><a href="{{route('info.page', ['pageName' => 'attacking-kingdoms'])}}">Attacking A Kingdom</a></li>
      </ul>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'settings'
          ])}}"><i class="fas fa-user-cog"></i> Player Settings</a>
    </li>
    <li class="nav-devider"></li>
    <li>
      <a href="{{route('info.page', [
          'pageName' => 'notifications'
        ])}}"><i class="fas fa-bell"></i> Notifications</a>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'market-board'
          ])}}"><i class="fas fa-sign"></i> Market Board</a>
    </li>
    <li class="nav-devider"></li>
    <li>
        <a class="has-arrow" href="#" aria-expanded="false"><i class="ra ra-anvil"></i><span class="hide-menu">Crafting/Enchanting</span></a>
        <ul aria-expanded="false" class="collapse">
            <li>
                <a href="{{route('info.page', ['pageName' => 'crafting'])}}">Crafting</a>
            </li>
            <li>
                <a href="{{route('info.page', ['pageName' => 'enchanting'])}}">Enchanting</a>
            </li>
        </ul>
    </li>
    <li>
        <a href="{{route('info.page', [
            'pageName' => 'monsters'
          ])}}"><i class="ra ra-eye-monster"></i> Monsters List</a>
    </li>
</ul>
