<x-cards.card-with-title title="Quest Item Details">
  @if (!is_null($item->effect))
    <p>This item lets you: {{$effects}}</p>
  @endif
  <x-core.alerts.info-alert>
    <p>
      Quest items, like this one are used automatically. For example if the quest item gives bonuses to a crafting skill or enchanting, then the skill bonus and xp
      will be applied upon crafting or enchanting. If its an item, like Flask of Fresh Air for example - then it gets used when you attempt to walk on water for the first time.
    </p>
  </x-core.alerts.info-alert>

  @if (!is_null($monster))
    <hr>
    <dl>
      <dt>Drops from: </dt>
      <dd>
        @guest
          <a href="{{route('info.page.monster', [
                                        'monster' => $monster->id
                                    ])}}">{{$monster->name}}</a>
        @else
          <a href="{{route('info.page.monster', [
                                        'monster' => $monster->id
                                    ])}}">{{$monster->name}}</a>
        @endif
      </dd>
      <dt>Drop chance: </dt>
      <dd>
        {{$monster->quest_item_drop_chance * 100}}%
      </dd>
    </dl>
  @endif
  @if (!is_null($location))
    <hr>
    <dl>
      <dt>Found By Visiting: </dt>
      <dd>
        @auth
          @if (auth()->user()->hasRole('Admin'))
            <a href="{{route('locations.location', [
                                        'location' => $location->id
                                    ])}}">{{$location->name}}</a>
          @else
            <a href="{{route('info.page.location', [
                                                'location' => $location->id
                                            ])}}">{{$location->name}}</a>
          @endif
        @else
          <a href="{{route('info.page.location', [
                                                'location' => $location->id
                                            ])}}">{{$location->name}}</a>
        @endauth

      </dd>
      <dt>X/Y: </dt>
      <dd>
        {{$location->x}} / {{$location->y}}
      </dd>
    </dl>
  @endif
  @if (!is_null($adventure))
    <hr>
    <dl>
      <dt>Adventure Name: </dt>
      <dd>
        @auth
          @if (auth()->user()->hasRole('Admin'))
            <a href="{{route('adventures.adventure', [
                                                'adventure' => $adventure->id
                                            ])}}">{{$adventure->name}}</a>
          @else
            <a href="{{route('info.page.adventure', [
                                                'adventure' => $adventure->id
                                            ])}}">{{$adventure->name}}</a>
          @endif
        @else
          <a href="{{route('info.page.adventure', [
                                                'adventure' => $adventure->id
                                            ])}}">{{$adventure->name}}</a>
        @endauth
      </dd>
      <dt>Location of adventure: </dt>
      <dd>
        @auth
          @if (auth()->user()->hasRole('Admin'))
            <a href="{{route('locations.location', [
                                                'location' => $location->id
                                            ])}}">{{$adventure->location->name}}</a>
          @else
            <a href="{{route('info.page.location', [
                                                'location' => $adventure->location->id
                                            ])}}">{{$adventure->location->name}}</a>
          @endif
        @else
          <a href="{{route('info.page.location', [
                                                'location' => $adventure->location->id
                                            ])}}">{{$adventure->location->name}}</a>
        @endauth

      </dd>
      <dt>X/Y: </dt>
      <dd>
        {{$adventure->location->x}} / {{$adventure->location->y}}
      </dd>
    </dl>
  @endif
  @if (!is_null($quest))
    <hr>
    <dl>
      <dt>Quest Name: </dt>
      <dd>
        @guest
          <a href="{{route('info.page.quest', [
                                                    'quest' => $quest->id
                                                ])}}">{{$quest->name}}</a>
        @else
          @if (auth()->user()->hasRole('Admin'))
            <a href="{{route('quests.show', [
                                                    'quest' => $quest->id
                                                ])}}">{{$quest->name}}</a>
          @else
            <a href="{{route('info.page.quest', [
                                                    'quest' => $quest->id
                                                ])}}">{{$quest->name}}</a>
          @endif
        @endguest
      </dd>
    </dl>
  @endif
  @if (!is_null($item->dropLocation))
    <hr />
    <dl>
      <dt>Drops only from<sup>*</sup>: </dt>
      <dd>{{$item->dropLocation->name}}</dd>
      <dt>At (X/Y):</dt>
      <dd>{{$item->dropLocation->x}}/{{$item->dropLocation->y}}</dd>
      <dt>Located on plane:</dt>
      <dd>{{$item->dropLocation->name}}</dd>
    </dl>
    <p class="mt-4"><sup>*</sup> Players cannot be auto battling for this item to drop. Looting in this location is capped at 45%. All drop chances are 1/1,000,000. Players may also eed to do relevant quests to access this location.</p>
  @endif
</x-cards.card-with-title>
@if (!is_null($item->xp_bonus))
    <x-cards.card-with-title title="XP Bonus">
        <x-core.alerts.info-alert>
            <p>
                These quest items help players to gain levels faster. However, there are two aspects to them:
            </p>
            <ul>
                <li>
                    How much of a boost % wise.
                </li>
                <li>
                    Does it ignore caps?
                </li>
            </ul>
            <p>
                As most players know there are three types of caps in this game when it comes to leveling: Soft (you get 50% of the remaining XP), Medium (You get 25% of the remaining XP)
                and Hard (You get 10% of the remaining XP) Cap.
                Soft cap starts at 1/2 the way to your max level, medium is 75% of the way and Hard is the last ten levels. If a quest item states
                it ignores caps, you will get all the XP + the bonus AFTER any relevant skill training deductions - regardless of level.
            </p>
            <p>
                If it does not say it ignores caps, You will NOT get the bonus once you hit soft cap which is 500 for those without the Sash of the Heavens, or half the current max level cap.
                At which point you will no longer get the XP bonus.
            </p>
        </x-core.alerts.info-alert>
        <dl>
            <dt>XP Bonus:</dt>
            <dd>{{$item->xp_bonus * 100}}%</dd>
            <dt>Ignores Caps:</dt>
            <dd>{{$item->ignores_caps ? 'Yes' : 'No'}}</dd>
        </dl>
    </x-cards.card-with-title>
@endif
@if (!is_null($item->devouring_light) || !is_null($item->devouring_darkness))
    <x-cards.card-with-title title="Voidance and Devoid">
      <x-core.alerts.info-alert>
        <p>Voidance and Devoidance come in two forms: Devouring Light and Devouring Darkness.</p>
        <p>Devouring Light on enemies will give them a chance to void you of your enchantments and artifacts and boons,
        making you use raw unmodified stats and damage to strike the enemy.</p>
        <p>Devouring Light on the player will void the enemy of their enchantment damage and artifacts.</p>
        <p>Devouring Darkness will have a chance on either side to void the others Devouring Light.</p>
        <p>If the enemy is devoided (Devouring Darkness has fired) they cannot then void you. A Devouring Darkness successful fire will void a Devouring Light chance of ever firiing,
        which then allows your Devouring Light a chance to fire and void the enemy for the battle.</p>
        <p>You can read more about how this works in the <a href="/information/voidance">help section</a>.</p>
      </x-core.alerts.info-alert>
      <dl>
        <dt>Devouring Light Chance:</dt>
        <dd>{{$item->devouring_light * 100}}%</dd>
        <dt>Devouring Darkness Chance:</dt>
        <dd>{{$item->devouring_darkness * 100}}%</dd>
      </dl>
    </x-cards.card-with-title>
@endif
