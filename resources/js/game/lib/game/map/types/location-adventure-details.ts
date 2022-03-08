export default interface LocationAdventureDetails {

    description: string;

    exp_bonus: number;

    gold_rush_chance: number;

    id: number;

    item_find_chance: number;

    levels: number;

    location_id: number;

    name: string;

    published: boolean;

    reward_item_id: number | null;

    skill_exp_bonus: number;

    time_per_level: number;
}
