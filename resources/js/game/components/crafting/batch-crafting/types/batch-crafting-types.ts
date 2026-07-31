import { BatchCraftingStatus } from "../batch-crafting-status-display";

export type { BatchCraftingStatus };

export type BatchType =
    | "craft"
    | "craft_and_enchant"
    | "enchant"
    | "alchemy"
    | "holy_oils"
    | "trinketry";

export type CraftMode =
    | "specific_item"
    | "experience"
    | "event"
    | "craft_set"
    | "craft_enchant_set";

export type CraftCategory = "weapon" | "armour" | "ring" | "spell";

export type AlchemyMode = "experience" | "amount";

export type EnchantMode = "event";

export type HolyOilMode = "selected" | "set";

export type OutputDestination =
    | "inventory"
    | "inventory_set"
    | "crafted_items_set";

export type InventorySetOption = {
    set_id: number;
    label: string;
    current_slots: number;
    max_slots: number;
    remaining_slots: number;
    equipped: boolean;
    is_batch_crafting_set: boolean;
};

export type Disposition =
    | "keep"
    // Legacy status/action-history value only. New starts must not expose this in selectable UI.
    | "keep_highest"
    | "sell"
    | "destroy"
    | "list"
    | "disenchant"
    | "keep_best_sell_rest"
    | "keep_best_disenchant_rest"
    | "keep_best_destroy_rest"
    | "use_now";

export type CraftableItem = {
    id: number;
    name: string;
    type: string;
    cost?: number;
    gold_dust_cost?: number;
    shards_cost?: number;
};

export type EnchantmentOption = {
    id: number;
    name: string;
    type: "prefix" | "suffix";
    cost: number;
    description: string;
    int_required: number;
    skill_level_required: number;
    str_mod: number;
    dex_mod: number;
    agi_mod: number;
    chr_mod: number;
    dur_mod: number;
    int_mod: number;
    focus_mod: number;
    str_reduction: number;
    dex_reduction: number;
    dur_reduction: number;
    int_reduction: number;
    chr_reduction: number;
    agi_reduction: number;
    focus_reduction: number;
    base_damage_mod: number;
    base_ac_mod: number;
    base_healing_mod: number;
    damage_amount: number;
    irresistible_damage: boolean;
    damage_can_stack: boolean;
    steal_life_amount: number;
    entranced_chance: number;
    devouring_light: number;
    skill_reduction: number;
    resistance_reduction: number;
    skill_name: string | null;
    skill_training_bonus: number;
    skill_bonus: number;
};

export type CraftEnchantSetPlanEntry = {
    prefixAffixId: number | null;
    suffixAffixId: number | null;
    selectedItemId: number | null;
    handSelectionType?: HandSelectionType;
    selectedWeaponType?: string | null;
};

export type CraftEnchantSetPlan = Record<string, CraftEnchantSetPlanEntry>;

export type CraftSetPlanEntry = {
    selectedItemId: number | null;
    handSelectionType?: HandSelectionType;
    selectedWeaponType?: string | null;
};

export type HandSelectionType =
    | "single_handed"
    | "shield"
    | "two_handed"
    | null;

export type CraftSetPlan = Record<string, CraftSetPlanEntry>;

export type CraftEnchantSetAvailableItem = {
    id: number;
    name: string;
    cost: number;
    skill_level_required: number;
    type: string;
    handedness: "single_handed" | "two_handed" | "shield" | null;
};

export type CraftEnchantSetPlanPreviewEntry = {
    key: string;
    label: string;
    category: "hand" | "armour" | "ring" | "spell";
    optional: boolean;
    included: boolean;
    target: { type?: string; crafting_type?: string };
    requested_selected_item_id: number | null;
    selected_item_available: boolean;
    selected_item_id: number | null;
    selected_item_name: string | null;
    selected_item_cost: number;
    selected_item_details: any | null;
    selected_item_type: string | null;
    selected_item_handedness: "single_handed" | "two_handed" | "shield" | null;
    prefix_cost: number;
    suffix_cost: number;
    available_items: CraftEnchantSetAvailableItem[];
};

export type HolyOilItem = {
    id: number;
    item_id: number;
    item: {
        name: string;
        holy_stacks: number;
        holy_stacks_applied: number;
    };
};

export type HolyOilOption = {
    id: number;
    item_id: number;
    amount: number;
    item: {
        name: string;
    };
};

export type BatchCraftingItemPreviewSnapshot = {
    name: string;
    type: string;
    affix_count?: number;
    is_unique?: boolean;
    is_mythic?: boolean;
    is_cosmic?: boolean;
    holy_stacks_applied?: number;
    full_item_details?: any | null;
};

export type AmountPreview = {
    selected_item: BatchCraftingItemPreviewSnapshot | null;
    requested_amount: number;
    completed_amount: number;
    remaining_requested_amount: number;
    per_item_cost: number;
    enchant_cost_per_item: number;
    total_per_item_cost: number;
    total_cost: number;
    available_gold: number;
    prefix_affix_name: string | null;
    suffix_affix_name: string | null;
    enchant_can_destroy_item: boolean;
    enchant_has_failure_risk: boolean;
    destination: string | null;
    destination_current_slots: number;
    destination_max_slots: number;
    destination_remaining_slots: number;
    effective_craftable_amount: number;
    capped: boolean;
} | null;

export type AlchemyAmountPreview = {
    selected_item: BatchCraftingItemPreviewSnapshot | null;
    requested_amount: number;
    completed_amount: number;
    remaining_requested_amount: number;
    gold_dust_cost_per_item: number;
    shards_cost_per_item: number;
    total_gold_dust_cost: number;
    total_shards_cost: number;
    available_gold_dust: number;
    available_shards: number;
    bag_current: number;
    bag_max: number;
    bag_remaining: number;
    effective_craftable_amount: number;
    capped: boolean;
} | null;

export type HolyOilPreviewItemEntry = {
    item: BatchCraftingItemPreviewSnapshot | null;
    full_item_details: any;
    target_slot_id: number;
    current_stacks: number;
    planned_applications: number;
    resulting_stacks: number;
    maximum_stacks: number;
    exact_gold_dust_cost: number;
    oils_consumed: number;
};

export type HolyOilSelectedPreview = {
    items: HolyOilPreviewItemEntry[];
    selected_oils_available: number;
    applications_planned: number;
    items_affected: number;
    exact_gold_dust_required: number;
    gold_dust_available: number;
    oils_not_applicable: number;
    unapplied_reason: string | null;
    capped: boolean;
} | null;

export type HolyOilSetPreview = {
    set_name: string;
    items: HolyOilPreviewItemEntry[];
    selected_oils_available: number;
    applications_planned: number;
    items_affected: number;
    exact_gold_dust_required: number;
    gold_dust_available: number;
    oils_not_applicable: number;
    unapplied_reason: string | null;
    capped: boolean;
} | null;

export type CostBreakdown = {
    currency: string;
    currency_label: string;
    required_to_start: number;
    available_currency_amount: number;
    can_afford_start: boolean;
    total_cost_known: boolean;
    total_required: number | null;
    effective_amount: number | null;
    destination: string | null;
    source_hint: string | null;
    message: string | null;
    planned_items?: number;
    configured_items?: number;
    craft_cost_total?: number;
    enchant_cost_total?: number;
    total_required_gold?: number;
    missing_currency_amount?: number;
    can_afford_full_plan?: boolean;
    total_required_gold_dust?: number;
    total_required_shards?: number;
    available_gold_dust?: number;
    available_shards?: number;
    enchant_has_failure_risk?: boolean;
    default_prefix_affix_id?: number | null;
    default_prefix_affix_name?: string | null;
    default_suffix_affix_id?: number | null;
    default_suffix_affix_name?: string | null;
    plan_entries?: CraftEnchantSetPlanPreviewEntry[];
    trinket?: {
        id: number;
        name: string;
    };
    gold_dust?: {
        required: number;
        available: number;
        missing: number;
    };
    copper_coins?: {
        required: number;
        available: number;
        missing: number;
    };
};

export type BatchCraftingStartBlockerLink = {
    url: string;
    label: string;
};

export type BatchCraftingStartBlocker = {
    code: string;
    message: string;
    blocking: boolean;
    links?: BatchCraftingStartBlockerLink[];
    plan_key?: string;
    selected_item_id?: number;
    target_label?: string;
    affix_id?: number;
    affix_name?: string;
    affix_type?: string;
    int_required?: number;
    character_int?: number;
};

export type DestinationCapacity = {
    destination:
        | "crafted_items_set"
        | "alchemy_bag"
        | "inventory"
        | "inventory_set";
    destination_label: string;
    current: number;
    max: number;
    remaining: number;
} | null;

export type BatchCraftingPreview = {
    cost_breakdown: CostBreakdown;
    amount_preview: AmountPreview;
    alchemy_amount_preview: AlchemyAmountPreview;
    holy_oil_selected_preview: HolyOilSelectedPreview;
    holy_oil_set_preview: HolyOilSetPreview;
    destination_capacity: DestinationCapacity;
    maximum_request_amount: number;
    start_blockers: BatchCraftingStartBlocker[];
};
