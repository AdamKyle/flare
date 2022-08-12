<table>
    <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>intro_text</th>
            <th>instructions</th>
            <th>required_level</th>
            <th>required_skill</th>
            <th>required_skill_level</th>
            <th>required_faction_id</th>
            <th>required_faction_level</th>
            <th>required_game_map_id</th>
            <th>required_quest_id</th>
            <th>required_quest_item_id</th>
            <th>required_kingdoms</th>
            <th>required_kingdom_level</th>
            <th>required_kingdom_units</th>
            <th>required_passive_skill</th>
            <th>required_passive_level</th>
            <th>reward_level</th>
            <th>gold_dust_reward</th>
            <th>shards_reward</th>
            <th>required_passive_skill</th>
            <th>required_passive_level</th>
        </tr>
    </thead>
    <tbody>
        @foreach($guideQuests as $guideQuest)
            <tr>
                <td>{{$guideQuest->id}}</td>
                <td>{{$guideQuest->name}}</td>
                <td>{{nl2br($guideQuest->intro_text)}}</td>
                <td>{{nl2br($guideQuest->instructions)}}</td>
                <td>{{$guideQuest->required_level}}</td>
                <td>{{$guideQuest->required_skill}}</td>
                <td>{{$guideQuest->required_skill_level}}</td>
                <td>{{$guideQuest->required_faction_id}}</td>
                <td>{{$guideQuest->required_faction_level}}</td>
                <td>{{$guideQuest->required_game_map_id}}</td>
                <td>{{$guideQuest->required_quest_id}}</td>
                <td>{{$guideQuest->required_quest_item_id}}</td>
                <td>{{$guideQuest->required_kingdoms}}</td>
                <td>{{$guideQuest->required_kingdom_level}}</td>
                <td>{{$guideQuest->required_kingdom_units}}</td>
                <td>{{$guideQuest->required_passive_skill}}</td>
                <td>{{$guideQuest->required_passive_level}}</td>
                <td>{{$guideQuest->reward_level}}</td>
                <td>{{$guideQuest->gold_dust_reward}}</td>
                <td>{{$guideQuest->shards_reward}}</td>
                <td>{{$guideQuest->required_passive_skill}}</td>
                <td>{{$guideQuest->required_passive_level}}</td>
            </tr>
        @endforeach
    </tbody>
</table>