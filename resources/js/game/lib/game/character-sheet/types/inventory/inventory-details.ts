export default interface InventoryDetails {

    attached_affixes_count: number;

    id: number;

    slot_id: number;

    item_id: number;

    is_unique: boolean;

    is_mythic: boolean;

    item_name: string;

    description: string;

    type: string;

    ac: number;

    attack: number;

    has_holy_stacks_applied: number,

    usable: boolean,
}
