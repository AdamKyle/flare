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

export type InventorySetOption = {
    set_id: number;
    label: string;
    current_slots: number;
    max_slots: number;
    remaining_slots: number;
};

export type Disposition =
    | "keep"
    | "keep_highest"
    | "sell"
    | "destroy"
    | "list"
    | "disenchant"
    | "keep_best_sell_rest"
    | "keep_best_disenchant_rest";

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
    cost?: number;
    int_required?: number;
};

export type CraftEnchantSetPlanEntry = {
    prefixAffixId: number | null;
    suffixAffixId: number | null;
};

export type CraftEnchantSetPlan = Record<string, CraftEnchantSetPlanEntry>;

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
    destination: string;
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
    current_stacks: number;
    max_stacks: number;
    remaining_capacity: number;
    gold_dust_cost_per_application: number;
};

export type HolyOilSelectedPreview = {
    items: HolyOilPreviewItemEntry[];
    total_eligible_items: number;
    total_remaining_applications: number;
    selected_oils_available: number;
    gold_dust_available: number;
    total_cost_if_fully_applied: number;
    max_applications_possible: number;
    capped: boolean;
} | null;

export type HolyOilSetPreview = {
    set_name: string;
    items: HolyOilPreviewItemEntry[];
    total_eligible_items: number;
    total_remaining_applications: number;
    selected_oils_available: number;
    gold_dust_available: number;
    total_cost_if_fully_applied: number;
    max_applications_possible: number;
    capped: boolean;
} | null;

export type BatchCraftingPreview = {
    amount_preview: AmountPreview;
    alchemy_amount_preview: AlchemyAmountPreview;
    holy_oil_selected_preview: HolyOilSelectedPreview;
    holy_oil_set_preview: HolyOilSetPreview;
};
