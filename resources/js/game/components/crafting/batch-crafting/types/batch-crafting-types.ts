import { BatchCraftingStatus } from "../batch-crafting-status-display";

export type { BatchCraftingStatus };

export type BatchType =
    | "craft"
    | "craft_and_enchant"
    | "alchemy"
    | "holy_oils"
    | "trinketry";

export type CraftMode = "specific_item" | "experience";

export type CraftCategory = "weapon" | "armour" | "ring" | "spell";

export type AlchemyMode = "experience" | "amount";

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
