<table>
<<<<<<< HEAD
  <thead>
    <tr>
      <th>name</th>
      <th>game_map_id</th>
      <th>quest_reward_item_id</th>
      <th>required_quest_item_id</th>
      <th>description</th>
      <th>is_port</th>
      <th>can_players_enter</th>
      <th>enemy_strength_increase</th>
      <th>can_auto_battle</th>
      <th>x</th>
      <th>y</th>
      <th>type</th>
      <th>drops_items</th>
      <th>pin_css_class</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($locations as $location)
      <tr>
        <td>{{ $location->name }}</td>
        <td>
          {{ ! is_null($location->game_map_id) ? $location->map->name : '' }}
        </td>
        <td>
          {{ ! is_null($location->quest_reward_item_id) ? $location->questRewardItem->name : '' }}
        </td>
        <td>
          {{ ! is_null($location->required_quest_item_id) ? $location->requiredQuestItem->name : '' }}
        </td>
        <td>{{ $location->description }}</td>
        <td>{{ $location->is_port }}</td>
        <td>{{ $location->can_players_enter }}</td>
        <td>{{ $location->enemy_strength_increase }}</td>
        <td>{{ $location->can_auto_battle }}</td>
        <td>{{ $location->x }}</td>
        <td>{{ $location->y }}</td>
        <td>{{ $location->type }}</td>
        <td>
          {{ ! is_null($location->enemy_strength_increase) ? 'Yes' : 'No' }}
        </td>
        <td>{{ $location->pin_css_class }}</td>
      </tr>
    @endforeach
  </tbody>
=======
    <thead>
        <tr>
            <th>name</th>
            <th>game_map_id</th>
            <th>quest_reward_item_id</th>
            <th>required_quest_item_id</th>
            <th>description</th>
            <th>is_port</th>
            <th>can_players_enter</th>
            <th>enemy_strength_type</th>
            <th>can_auto_battle</th>
            <th>x</th>
            <th>y</th>
            <th>type</th>
            <th>drops_items</th>
            <th>pin_css_class</th>
            <th>hours_to_drop</th>
            <th>minutes_between_delve_fights</th>
            <th>delve_enemy_strength_increase</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($locations as $location)
            <tr>
                <td>{{ $location->name }}</td>
                <td>{{ !is_null($location->game_map_id) ? $location->map->name : '' }}</td>
                <td>{{ !is_null($location->quest_reward_item_id) ? $location->questRewardItem->name : '' }}</td>
                <td>{{ !is_null($location->required_quest_item_id) ? $location->requiredQuestItem->name : '' }}</td>
                <td>{{ $location->description }}</td>
                <td>{{ $location->is_port }}</td>
                <td>{{ $location->can_players_enter }}</td>
                <td>{{ $location->enemy_strength_type }}</td>
                <td>{{ $location->can_auto_battle }}</td>
                <td>{{ $location->x }}</td>
                <td>{{ $location->y }}</td>
                <td>{{ $location->type }}</td>
                <td>{{ !is_null($location->enemy_strength_type) ? 'Yes' : 'No' }}</td>
                <td>{{ $location->pin_css_class }}</td>
                <th>{{ $location->hours_to_drop }}</th>
                <td>{{ $location->minutes_between_delve_fights }}</td>
                <td>{{ $location->delve_enemy_strength_increase }}</td>
            </tr>
        @endforeach
    </tbody>
>>>>>>> master
</table>
