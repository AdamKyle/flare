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
            <th>required_secondary_skill</th>
            <th>required_secondary_skill_level</th>
            <th>required_skill_type</th>
            <th>required_skill_type_level</th>
            <th>required_mercenary_type</th>
            <th>required_secondary_mercenary_type</th>
            <th>required_mercenary_level</th>
            <th>required_secondary_mercenary_level</th>
            <th>required_class_specials_equipped</th>
            <th>required_faction_id</th>
            <th>required_faction_level</th>
            <th>required_game_map_id</th>
            <th>required_quest_id</th>
            <th>required_quest_item_id</th>
            <th>secondary_quest_item_id</th>
            <th>required_gold</th>
            <th>required_gold_dust</th>
            <th>required_shards</th>
            <th>required_kingdoms</th>
            <th>required_kingdom_level</th>
            <th>required_kingdom_units</th>
            <th>required_passive_skill</th>
            <th>required_passive_level</th>
            <th>required_stats</th>
            <th>required_str</th>
            <th>required_dex</th>
            <th>required_agi</th>
            <th>required_int</th>
            <th>required_dur</th>
            <th>required_chr</th>
            <th>required_focus</th>
            <th>gold_dust_reward</th>
            <th>shards_reward</th>
            <th>gold_reward</th>
            <th>xp_reward</th>
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
                <td>{{!is_null($guideQuest->required_skill) ? $guideQuest->skill_name : ''}}</td>
                <td>{{$guideQuest->required_skill_level}}</td>
                <td>{{!is_null($guideQuest->required_secondary_skill) ? $guideQuest->secondary_skill_name : ''}}</td>
                <td>{{$guideQuest->required_secondary_skill_level}}</td>
                <td>{{$guideQuest->required_skill_type}}</td>
                <td>{{$guideQuest->required_skill_type_level}}</td>
                <td>{{$guideQuest->required_mercenary_type}}</td>
                <td>{{$guideQuest->required_secondary_mercenary_type}}</td>
                <td>{{$guideQuest->required_mercenary_level}}</td>
                <td>{{$guideQuest->required_secondary_mercenary_level}}</td>
                <td>{{$guideQuest->required_class_specials_equipped}}</td>
                <td>{{!is_null($guideQuest->required_faction_id) ? $guideQuest->faction_name : ''}}</td>
                <td>{{$guideQuest->required_faction_level}}</td>
                <td>{{!is_null($guideQuest->required_game_map_id) ? $guideQuest->game_map_name : ''}}</td>
                <td>{{!is_null($guideQuest->required_quest_id) ? $guideQuest->quest_name : ''}}</td>
                <td>{{!is_null($guideQuest->required_quest_item_id) ? $guideQuest->quest_item_name : ''}}</td>
                <td>{{!is_null($guideQuest->secondary_quest_item_id) ? $guideQuest->secondary_quest_item_name : ''}}</td>
                <td>{{!is_null($guideQuest->required_quest_item_id) ? $guideQuest->required_gold : ''}}</td>
                <td>{{!is_null($guideQuest->required_quest_item_id) ? $guideQuest->required_gold_dust : ''}}</td>
                <td>{{!is_null($guideQuest->required_quest_item_id) ? $guideQuest->required_shards : ''}}</td>
                <td>{{$guideQuest->required_kingdoms}}</td>
                <td>{{$guideQuest->required_kingdom_level}}</td>
                <td>{{$guideQuest->required_kingdom_units}}</td>
                <td>{{!is_null($guideQuest->required_passive_skill) ? $guideQuest->passive_name : ''}}</td>
                <td>{{$guideQuest->required_passive_level}}</td>
                <td>{{$guideQuest->required_stats}}</td>
                <td>{{$guideQuest->required_str}}</td>
                <td>{{$guideQuest->required_dex}}</td>
                <td>{{$guideQuest->required_agi}}</td>
                <td>{{$guideQuest->required_int}}</td>
                <td>{{$guideQuest->required_dur}}</td>
                <td>{{$guideQuest->required_chr}}</td>
                <td>{{$guideQuest->required_focus}}</td>
                <td>{{$guideQuest->gold_dust_reward}}</td>
                <td>{{$guideQuest->shards_reward}}</td>
                <td>{{$guideQuest->gold_reward}}</td>
                <td>{{$guideQuest->xp_reward}}</td>+
            </tr>
        @endforeach
    </tbody>
</table>
