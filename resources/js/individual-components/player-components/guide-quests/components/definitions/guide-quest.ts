export default interface GuideQuest {
    [key: string]: any;
    id: number;
    name: string;
    intro_text: string;
    instructions: string;
    required_level: number | null;
    required_skill: number | null;
    required_skill_level: number | null;
    required_faction_id: number | null;
    required_faction_level: number | null;
    required_game_map_id: number | null;
    required_quest_id: number | null;
    required_quest_item_id: number | null;
    gold_dust_reward: number | null;
    shards_reward: number | null;
    required_kingdoms: number | null;
    required_kingdom_level: number | null;
    required_kingdom_units: number | null;
    required_passive_skill: number | null;
    required_passive_level: number | null;
    faction_points_per_kill: number | null;
    required_shards: number | null;
    xp_reward: number;
    gold_reward: number | null;
    required_gold_dust: number | null;
    required_gold: number | null;
    required_stats: number | null;
    required_str: number | null;
    required_dex: number | null;
    required_int: number | null;
    required_dur: number | null;
    required_chr: number | null;
    required_agi: number | null;
    required_focus: number | null;
    required_secondary_skill: number | null;
    required_secondary_skill_level: number | null;
    secondary_quest_item_id: number | null;
    required_skill_type: number | null;
    required_skill_type_level: number | null;
    required_mercenary_type: number | null
    required_secondary_mercenary_type: number | null;
    required_mercenary_level: number | null;
    required_secondary_mercenary_level: number | null;
    required_class_specials_equipped: number | null;
    desktop_instructions: string;
    mobile_instructions: string;
    skill_name: string | null;
    faction_name: string | null;
    game_map_name: string | null;
    quest_name: string | null;
    quest_item_name: string | null;
    secondary_quest_item_name: string | null;
    passive_name: string | null;
    secondary_skill_name: string | null;
    skill_type_name: string | null;
    mercenary_name: string | null;
    secondary_mercenary_name: string | null;
}