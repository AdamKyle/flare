export default interface ExtraActionType {
    chance: number;

    class_id: number;

    class_name: string;

    class_weapons: string[];

    attack_type: string;

    equipped_class_items: {
        item_id: number;
        item_name: string;
        type: string;
        attached_affixes_count: number;
        is_unique: boolean;
        is_mythic: boolean;
        is_cosmic: boolean;
        has_holy_stacks_applied: number;
    }[];

    has_item: boolean;

    only: string;

    type: string;

    amount?: number;
}
