export default interface LocationDetails {
    id: number;

    name: string;

    description: string;

    x: number;

    y: number;

    is_port: boolean;

    enemy_strength_type: string | null;

    increase_enemy_percentage_by: number | null;

    increases_enemy_stats_by: number | null;

    quest_reward_item: any;

    quest_reward_item_id: number | null;

    required_quest_item_id: number | null;

    required_quest_item_name: string | null;

    type: string | null;

    type_name: string | null;

    is_corrupted: boolean;

    has_raid_boss: boolean;

    pin_css_class: string | null;

    game_map_name: string;

    game_map_id: number;

    minutes_between_delve_fights: number;

    delve_enemy_strength_increase: number;

    hours_to_drop: number;
}
