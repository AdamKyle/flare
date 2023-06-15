<table>
    <thead>
    <tr>
        <th>id</th>
        <th>name</th>
        <th>npc_id</th>
        <th>raid_id</th>
        <th>required_quest_id</th>
        <th>reincarnated_times</th>
        <th>item_name</th>
        <th>gold_dust_cost</th>
        <th>shard_cost</th>
        <th>gold_cost</th>
        <th>copper_coin_cost</th>
        <th>reward_item_name</th>
        <th>reward_gold_dust</th>
        <th>reward_shards</th>
        <th>reward_gold</th>
        <th>reward_xp</th>
        <th>unlocks_skill</th>
        <th>unlocks_skill_type</th>
        <th>is_parent</th>
        <th>parent_quest_id</th>
        <th>secondary_required_item_name</th>
        <th>faction_game_map_id</th>
        <th>required_faction_level</th>
        <th>unlocks_feature</th>
        <th>unlocks_passive_id</th>
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
            <td>{{!is_null($quest->item) ? $quest->item->name : ''}}</td>
            <td>{{$quest->gold_dust_cost}}</td>
            <td>{{$quest->shard_cost}}</td>
            <td>{{$quest->gold_cost}}</td>
            <td>{{$quest->copper_coin_cost}}</td>
            <td>{{!is_null($quest->rewardItem) ? $quest->rewardItem->name : ''}}</td>
            <td>{{$quest->reward_gold_dust}}</td>
            <td>{{$quest->reward_shards}}</td>
            <td>{{$quest->reward_gold}}</td>
            <td>{{$quest->reward_xp}}</td>
            <td>{{!is_null($quest->unlocks_skill) ? $quest->unlocks_skill_name : ''}}</td>
            <td>{{$quest->unlocks_skill_type}}</td>
            <td>{{$quest->is_parent}}</td>
            <td>{{!is_null($quest->parent_quest_id) ? $quest->parent_quest_id : ''}}</td>
            <td>{{!is_null($quest->secondary_required_item) ? $quest->secondaryItem->name : ''}}</td>
            <td>{{!is_null($quest->faction_game_map_id) ? $quest->faction_game_map_id : ''}}</td>
            <td>{{!is_null($quest->required_faction_level) ? $quest->required_faction_level : ''}}</td>
            <td>{{!is_null($quest->unlocks_feature) ? $quest->unlocks_feature : ''}}</td>
            <td>{{!is_null($quest->unlocks_passive_id) ? $quest->unlocks_passive_id : ''}}</td>
            <td>{{nl2br($quest->before_completion_description)}}</td>
            <td>{{nl2br($quest->after_completion_description)}}</td>
            <td>{{!is_null($quest->raid_id) ? $quest->raid->name : ''}}</td>
            <td>{{!is_null($quest->required_quest_id) ? $quest->requiredQuest->name : ''}}</td>
            <td>{{$quest->reincarnated_times}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
