export default interface EventGoal {
    max_kills: number | null;
    max_crafts: number | null;
    max_enchants: number | null;
    total_kills: number | null;
    total_crafts: number | null;
    total_enchants: number | null;
    reward_every: number;
    amount_needed_for_reward: number;
    current_kills: number;
    current_crafts: number;
    current_enchants: number;
    reward: string;
    should_be_mythic: boolean;
    should_be_unique: boolean;
}
