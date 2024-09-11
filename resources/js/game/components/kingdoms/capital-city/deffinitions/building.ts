export default interface Building {
    id: number;
    name: string;
    description: string;
    level: number;
    max_level: number;
    current_defence: number;
    max_defence: number;
    current_durability: number;
    max_durability: number;
    wood_cost: number;
    stone_cost: number;
    clay_cost: number;
    iron_cost: number;
}
