import LocationAdventureDetails from "./location-adventure-details";

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

    quest_reward_item: number | null;

    quest_reward_item_id: number | null;

    required_quest_item_id: number | null;

    type: string | null;

}
