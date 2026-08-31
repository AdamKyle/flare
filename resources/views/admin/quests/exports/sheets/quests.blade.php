<table>
    <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>npc_id</th>
            <th>item_id</th>
            <th>raid_id</th>
            <th>required_quest_id</th>
            <th>parent_chain_quest_id</th>
            <th>required_quest_chain</th>
            <th>reincarnated_times</th>
            <th>access_to_map_id</th>
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
            <th>unlocks_feature</th>
            <th>unlocks_passive_id</th>
            <th>only_for_event</th>
            <th>assisting_npc_id</th>
            <th>required_fame_level</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($quests as $quest)
            <tr>
                <td>{{ $quest->id }}</td>
                <td>{{ $quest->name }}</td>
                <td>{{ optional($quest->npc)->real_name }}</td>
                <td>{{ optional($quest->item)->name }}</td>
                <td>{{ optional($quest->raid)->name }}</td>
                <td>{{ optional($quest->requiredQuest)->name }}</td>
                <td>{{ $quest->parent_chain_quest_id }}</td>
                <td>
                    {{ collect($quest->required_quest_chain ?? [])->map(fn ($id) => $questNamesById->get($id))->filter()->implode(',') }}
                </td>
                <td>{{ $quest->reincarnated_times }}</td>
                <td>{{ optional($quest->requiredPlane)->name }}</td>
                <td>{{ $quest->gold_dust_cost }}</td>
                <td>{{ $quest->shard_cost }}</td>
                <td>{{ $quest->gold_cost }}</td>
                <td>{{ $quest->copper_coin_cost }}</td>
                <td>{{ optional($quest->rewardItem)->name }}</td>
                <td>{{ $quest->reward_gold_dust }}</td>
                <td>{{ $quest->reward_shards }}</td>
                <td>{{ $quest->reward_gold }}</td>
                <td>{{ $quest->reward_xp }}</td>
                <td>{{ $quest->unlocks_skill }}</td>
                <td>{{ $quest->unlocks_skill_type }}</td>
                <td>{{ $quest->is_parent }}</td>
                <td>{{ optional($quest->parent)->name }}</td>
                <td>{{ optional($quest->secondaryItem)->name }}</td>
                <td>{{ optional($quest->factionMap)->name }}</td>
                <td>{{ $quest->required_faction_level }}</td>
                <td>{{ $quest->before_completion_description }}</td>
                <td>{{ $quest->after_completion_description }}</td>
                <td>{{ $quest->unlocks_feature }}</td>
                <td>{{ optional($quest->passive)->name }}</td>
                <td>{{ $quest->only_for_event }}</td>
                <td>{{ optional($quest->factionLoyaltyNpc)->real_name }}</td>
                <td>{{ $quest->required_fame_level }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
