export type UnitLogDetails = { name: string; amount: number };

export type BuildingLogDetails = { name: string; durability: number; }

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

    units_sent: UnitLogDetails[]|[];

    units_survived: UnitLogDetails[]|[];

    old_buildings: BuildingLogDetails[]|[];

    new_buildings: BuildingLogDetails[]|[];

    old_units: UnitLogDetails[]|[];

    new_units: UnitLogDetails[]|[];

    item_damage: number;

    morale_loss: number;

    opened: boolean;

    created_at: string;

    took_kingdom: boolean;

    // This can be anything that we want to send back.
    additional_details: any;

}
