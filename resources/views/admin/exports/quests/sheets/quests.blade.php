<table>
    <thead>
    <tr>
        <th>id</th>
        <th>name</th>
        <th>npc_id</th>
        <th>item_id</th>
        <th>gold_dust_cost</th>
        <th>shard_cost</th>
        <th>gold_cost</th>
        <th>copper_coin_cost</th>
        <th>reward_item</th>
        <th>reward_gold_dust</th>
        <th>reward_shards</th>
        <th>reward_gold</th>
        <th>reward_xp</th>
        <th>unlocks_skill</th>
        <th>unlocks_skill_type</th>
        <th>is_parent</th>
        <th>parent_quest_id</th>
        <th>secondary_required_item</th>
        <th>faction_game_map_id</th>
        <th>required_faction_level</th>
        <th>before_completion_description</th>
        <th>after_completion_description</th>
    </tr>
    </thead>
    <tbody>
    @foreach($quests as $quest)
        <tr>
            <td>{{$quest->id}}</td>
            <td>{{$quest->name}}</td>
            <td>{{$quest->npc->id}}</td>
            <td>{{!is_null($quest->item) ? $quest->item->id : ''}}</td>
            <td>{{$quest->gold_dust_cost}}</td>
            <td>{{$quest->shard_cost}}</td>
            <td>{{$quest->gold_cost}}</td>
            <td>{{$quest->copper_coin_cost}}</td>
            <td>{{!is_null($quest->rewardItem) ? $quest->rewardItem->id : ''}}</td>
            <td>{{$quest->reward_gold_dust}}</td>
            <td>{{$quest->reward_shards}}</td>
            <td>{{$quest->reward_gold}}</td>
            <td>{{$quest->reward_xp}}</td>
            <td>{{!is_null($quest->unlocks_skill) ? $quest->unlocks_skill_name : ''}}</td>
            <td>{{$quest->unlocks_skill_type}}</td>
            <td>{{$quest->is_parent}}</td>
            <td>{{!is_null($quest->parent_quest_id) ? $quest->parent_quest_id : ''}}</td>
            <td>{{!is_null($quest->secondary_required_item) ? $quest->secondary_required_item : ''}}</td>
            <td>{{!is_null($quest->faction_game_map_id) ? $quest->faction_game_map_id : ''}}</td>
            <td>{{!is_null($quest->required_faction_level) ? $quest->required_faction_level : ''}}</td>
            <td>{{nl2br($quest->before_completion_description)}}</td>
            <td>{{nl2br($quest->after_completion_description)}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
