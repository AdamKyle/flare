<table>
<<<<<<< HEAD
  <thead>
    <tr>
      <th>id</th>
      <th>name</th>
      <th>intro_text</th>
      <th>instructions</th>
      <th>desktop_instructions</th>
      <th>mobile_instructions</th>
      <th>required_level</th>
      <th>required_skill</th>
      <th>required_skill_level</th>
      <th>required_secondary_skill</th>
      <th>required_secondary_skill_level</th>
      <th>required_skill_type</th>
      <th>required_skill_type_level</th>
      <th>required_class_specials_equipped</th>
      <th>required_class_rank_level</th>
      <th>required_faction_id</th>
      <th>required_faction_level</th>
      <th>required_game_map_id</th>
      <th>required_quest_id</th>
      <th>required_quest_item_id</th>
      <th>secondary_quest_item_id</th>
      <th>required_gold</th>
      <th>required_gold_dust</th>
      <th>required_shards</th>
      <th>required_copper_coins</th>
      <th>required_gold_bars</th>
      <th>required_kingdoms</th>
      <th>required_kingdom_level</th>
      <th>required_kingdom_units</th>
      <th>required_kingdom_building_id</th>
      <th>required_kingdom_building_level</th>
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
      <th>parent_id</th>
      <th></th>
      <th>unlock_at_level</th>
      <th></th>
      <th>only_during_event</th>
      <th></th>
      <th>be_on_game_map</th>
      <th>required_event_goal_participation</th>
      <th>required_holy_stacks</th>
      <th>required_attached_gems</th>
      <th>required_specialty_type</th>
      <th>required_fame_level</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($guideQuests as $guideQuest)
      <tr>
        <td>{{ $guideQuest->id }}</td>
        <td>{{ $guideQuest->name }}</td>
        <td>{{ nl2br($guideQuest->intro_text) }}</td>
        <td>{{ nl2br($guideQuest->instructions) }}</td>
        <td>{{ nl2br($guideQuest->desktop_instructions) }}</td>
        <td>{{ nl2br($guideQuest->mobile_instructions) }}</td>
        <td>{{ $guideQuest->required_level }}</td>
        <td>
          {{ ! is_null($guideQuest->required_skill) ? $guideQuest->skill_name : '' }}
        </td>
        <td>{{ $guideQuest->required_skill_level }}</td>
        <td>
          {{ ! is_null($guideQuest->required_secondary_skill) ? $guideQuest->secondary_skill_name : '' }}
        </td>
        <td>{{ $guideQuest->required_secondary_skill_level }}</td>
        <td>{{ $guideQuest->required_skill_type }}</td>
        <td>{{ $guideQuest->required_skill_type_level }}</td>
        <td>{{ $guideQuest->required_class_specials_equipped }}</td>
        <td>{{ $guideQuest->required_class_rank_level }}</td>
        <td>
          {{ ! is_null($guideQuest->required_faction_id) ? $guideQuest->faction_name : '' }}
        </td>
        <td>{{ $guideQuest->required_faction_level }}</td>
        <td>
          {{ ! is_null($guideQuest->required_game_map_id) ? $guideQuest->game_map_name : '' }}
        </td>
        <td>
          {{ ! is_null($guideQuest->required_quest_id) ? $guideQuest->quest_name : '' }}
        </td>
        <td>
          {{ ! is_null($guideQuest->required_quest_item_id) ? $guideQuest->quest_item_name : '' }}
        </td>
        <td>
          {{ ! is_null($guideQuest->secondary_quest_item_id) ? $guideQuest->secondary_quest_item_name : '' }}
        </td>
        <td>{{ $guideQuest->required_gold }}</td>
        <td>{{ $guideQuest->required_gold_dust }}</td>
        <td>{{ $guideQuest->required_shards }}</td>
        <td>{{ $guideQuest->required_copper_coins }}</td>
        <td>{{ $guideQuest->required_gold_bars }}</td>
        <td>{{ $guideQuest->required_kingdoms }}</td>
        <td>{{ $guideQuest->required_kingdom_level }}</td>
        <td>{{ $guideQuest->required_kingdom_units }}</td>
        <td>
          {{ ! is_null($guideQuest->required_kingdom_building_id) ? $guideQuest->kingdom_building_name : '' }}
        </td>
        <td>{{ $guideQuest->required_kingdom_building_level }}</td>
        <td>
          {{ ! is_null($guideQuest->required_passive_skill) ? $guideQuest->passive_name : '' }}
        </td>
        <td>{{ $guideQuest->required_passive_level }}</td>
        <td>{{ $guideQuest->required_stats }}</td>
        <td>{{ $guideQuest->required_str }}</td>
        <td>{{ $guideQuest->required_dex }}</td>
        <td>{{ $guideQuest->required_agi }}</td>
        <td>{{ $guideQuest->required_int }}</td>
        <td>{{ $guideQuest->required_dur }}</td>
        <td>{{ $guideQuest->required_chr }}</td>
        <td>{{ $guideQuest->required_focus }}</td>
        <td>{{ $guideQuest->gold_dust_reward }}</td>
        <td>{{ $guideQuest->shards_reward }}</td>
        <td>{{ $guideQuest->gold_reward }}</td>
        <td>{{ $guideQuest->xp_reward }}</td>
        <td>
          {{ ! is_null($guideQuest->parent_id) ? $guideQuest->parent_quest_name : '' }}
        </td>
        <td></td>
        <td>{{ $guideQuest->unlock_at_level }}</td>
        <td></td>
        <td>{{ $guideQuest->only_during_event }}</td>
        <td></td>
        <td>
          {{ ! is_null($guideQuest->be_on_game_map) ? $guideQuest->required_to_be_on_game_map_name : '' }}
        </td>
        <td>{{ $guideQuest->required_event_goal_participation }}</td>
        <td>{{ $guideQuest->required_holy_stacks }}</td>
        <td>{{ $guideQuest->required_attached_gems }}</td>
        <td>{{ $guideQuest->required_specialty_type }}</td>
        <td>{{ $guideQuest->required_fame_level }}</td>
      </tr>
    @endforeach
  </tbody>
=======
    <thead>
        <tr>
            <th>id</th>
            <th>name</th>
            <th>intro_text</th>
            <th>instructions</th>
            <th>desktop_instructions</th>
            <th>mobile_instructions</th>
            <th>required_level</th>
            <th>required_reincarnation_amount</th>
            <th>required_skill</th>
            <th>required_skill_level</th>
            <th>required_secondary_skill</th>
            <th>required_secondary_skill_level</th>
            <th>required_skill_type</th>
            <th>required_skill_type_level</th>
            <th>required_class_specials_equipped</th>
            <th>required_class_rank_level</th>
            <th>required_faction_id</th>
            <th>required_faction_level</th>
            <th>required_game_map_id</th>
            <th>required_quest_id</th>
            <th>required_quest_item_id</th>
            <th>secondary_quest_item_id</th>
            <th>required_gold</th>
            <th>required_gold_dust</th>
            <th>required_shards</th>
            <th>required_copper_coins</th>
            <th>required_gold_bars</th>
            <th>required_kingdoms</th>
            <th>required_kingdom_level</th>
            <th>required_kingdom_units</th>
            <th>required_kingdom_building_id</th>
            <th>required_kingdom_building_level</th>
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
            <th>parent_id<th>
            <th>unlock_at_level<th>
            <th>only_during_event<th>
            <th>be_on_game_map</th>
            <th>required_event_goal_participation</th>
            <th>required_holy_stacks</th>
            <th>required_attached_gems</th>
            <th>required_specialty_type</th>
            <th>required_fame_level</th>
            <th>required_delve_survival_time</th>
            <th>required_delve_pack_size</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($guideQuests as $guideQuest)
            <tr>
                <td>{{ $guideQuest->id }}</td>
                <td>{{ $guideQuest->name }}</td>
                <td>{{ nl2br($guideQuest->intro_text) }}</td>
                <td>{{ nl2br($guideQuest->instructions) }}</td>
                <td>{{ nl2br($guideQuest->desktop_instructions) }}
                <td>{{ nl2br($guideQuest->mobile_instructions) }}
                <td>{{ $guideQuest->required_level }}</td>
                <td>{{ $guideQuest->required_reincarnation_amount }}</td>
                <td>{{ !is_null($guideQuest->required_skill) ? $guideQuest->skill_name : '' }}</td>
                <td>{{ $guideQuest->required_skill_level }}</td>
                <td>{{ !is_null($guideQuest->required_secondary_skill) ? $guideQuest->secondary_skill_name : '' }}</td>
                <td>{{ $guideQuest->required_secondary_skill_level }}</td>
                <td>{{ $guideQuest->required_skill_type }}</td>
                <td>{{ $guideQuest->required_skill_type_level }}</td>
                <td>{{ $guideQuest->required_class_specials_equipped }}</td>
                <td>{{ $guideQuest->required_class_rank_level }}</td>
                <td>{{ !is_null($guideQuest->required_faction_id) ? $guideQuest->faction_name : '' }}</td>
                <td>{{ $guideQuest->required_faction_level }}</td>
                <td>{{ !is_null($guideQuest->required_game_map_id) ? $guideQuest->game_map_name : '' }}</td>
                <td>{{ !is_null($guideQuest->required_quest_id) ? $guideQuest->quest_name : '' }}</td>
                <td>{{ !is_null($guideQuest->required_quest_item_id) ? $guideQuest->quest_item_name : '' }}</td>
                <td>{{ !is_null($guideQuest->secondary_quest_item_id) ? $guideQuest->secondary_quest_item_name : '' }}
                </td>
                <td>{{ $guideQuest->required_gold }}</td>
                <td>{{ $guideQuest->required_gold_dust }}</td>
                <td>{{ $guideQuest->required_shards }}</td>
                <td>{{ $guideQuest->required_copper_coins }}</td>
                <td>{{ $guideQuest->required_gold_bars }}</td>
                <td>{{ $guideQuest->required_kingdoms }}</td>
                <td>{{ $guideQuest->required_kingdom_level }}</td>
                <td>{{ $guideQuest->required_kingdom_units }}</td>
                <td>{{ !is_null($guideQuest->required_kingdom_building_id) ? $guideQuest->kingdom_building_name : '' }}
                </td>
                <td>{{ $guideQuest->required_kingdom_building_level }}</td>
                <td>{{ !is_null($guideQuest->required_passive_skill) ? $guideQuest->passive_name : '' }}</td>
                <td>{{ $guideQuest->required_passive_level }}</td>
                <td>{{ $guideQuest->required_stats }}</td>
                <td>{{ $guideQuest->required_str }}</td>
                <td>{{ $guideQuest->required_dex }}</td>
                <td>{{ $guideQuest->required_agi }}</td>
                <td>{{ $guideQuest->required_int }}</td>
                <td>{{ $guideQuest->required_dur }}</td>
                <td>{{ $guideQuest->required_chr }}</td>
                <td>{{ $guideQuest->required_focus }}</td>
                <td>{{ $guideQuest->gold_dust_reward }}</td>
                <td>{{ $guideQuest->shards_reward }}</td>
                <td>{{ $guideQuest->gold_reward }}</td>
                <td>{{ $guideQuest->xp_reward }}</td>
                <td>{{ !is_null($guideQuest->parent_id) ? $guideQuest->parent_quest_name : ''}}<td>
                <td>{{ $guideQuest->unlock_at_level}}<td>
                <td>{{ $guideQuest->only_during_event}}<td>
                <td>{{ !is_null($guideQuest->be_on_game_map) ? $guideQuest->required_to_be_on_game_map_name : ''}}</td>
                <td>{{ $guideQuest->required_event_goal_participation }}</td>
                <td>{{ $guideQuest->required_holy_stacks }}</td>
                <td>{{ $guideQuest->required_attached_gems }}</td>
                <td>{{ $guideQuest->required_specialty_type }}</td>
                <td>{{ $guideQuest->required_fame_level }}</td>
                <td>{{ $guideQuest->required_delve_survival_time }}</td>
                <td>{{ $guideQuest->required_delve_pack_size }}</td>
            </tr>
        @endforeach
    </tbody>
>>>>>>> master
</table>
