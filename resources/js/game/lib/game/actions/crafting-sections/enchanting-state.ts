import SkillXP from "../../../../sections/game-actions-section/components/deffinitions/skill-xp";

export interface EnchantingState {

    loading: boolean;

    selected_item: number | null;

    selected_prefix: number | null;

    selected_suffix: number | null;

    selected_type: string | null;

    enchantable_items: Enchantment[];

    enchantments: ItemToEnchant[];

    event_items: ItemToEnchant[]|[];

    show_enchanting_for_event: boolean;

    skill_xp: SkillXP

    info_message: string | null;
}

export type ItemToEnchant = {
    ac: number;

    attached_affixes_count: number;

    attack: number;

    description: string;

    has_holy_stacks_applied: number;

    holy_stacks: number;

    id: number;

    is_mythic: boolean;

    is_unique: boolean;

    item_id: number;

    item_name: string;

    position: string;

    slot_id: number;

    type: string;

    usable: boolean;
}

export type Enchantment = {
    cost: number;

    id: number;

    name: string;

    type: string;
}
