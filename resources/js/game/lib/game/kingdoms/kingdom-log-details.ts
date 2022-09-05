export default interface KingdomLogDetails {

    id: number;

    character_id: number;

    is_mine: boolean;

    attacking_character_name: string|null;

    from_kingdom_name: string|null;

    to_kingdom_name: string;

    to_x: number|null;

    to_y: number|null;

    from_x: number|null;

    from_y: number|null;

    status: string;

    units_sent: { name: string; amount: number }[]|[];

    units_survived: { name: string; amount: number; }[]|[];

    old_buildings: { name: string; durability: number; }[]|[];

    new_buildings: { name: string; durability: number; }[]|[];

    old_units: { name: string; amount: number; }[]|[];

    new_units: { name: string; amount: number; }[]|[];

    item_damage: number;

    morale_loss: number;

    opened: boolean;

    created_at: string;

}
