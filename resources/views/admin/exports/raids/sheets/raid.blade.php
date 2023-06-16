<table>
    <thead>
        <tr>
            <th>name</th>
            <th>story</th>
            <th>raid_boss_id</th>
            <th>raid_monster_ids</th>
            <th>raid_boss_location_id</th>
            <th>corrupted_location_ids</th>
            <th>item_specialty_reward_type</th>
        </tr>
    </thead>
    <tbody>
    @foreach($raids as $raid)
        <tr>
            <td>{{$raid->name}}</td>
            <td>{{$raid->story}}</td>
            <td>{{!is_null($raid->raid_boss_id) ? $raid->raidBoss->name : ''}}</td>
            <td>{{!is_null($raid->raid_monster_ids) ? implode(',', \App\Flare\Models\Monster::whereIn('id', $raid->raid_monster_ids)->pluck('name')->toArray()) : ''}}
            <td>{{!is_null($raid->raid_boss_location_id) ? $raid->raidBossLocation->name : ''}}</td>
            <td>{{!is_null($raid->corrupted_location_ids) ? implode(',', \App\Flare\Models\Location::whereIn('id', $raid->corrupted_location_ids)->pluck('name')->toArray()) : ''}}</td>
            <td>{{$raid->item_specialty_reward_type}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
