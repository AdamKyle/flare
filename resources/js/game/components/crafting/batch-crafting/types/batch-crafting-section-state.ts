import {
    AlchemyMode,
    BatchCraftingPreview,
    BatchCraftingStatus,
    BatchType,
    CraftableItem,
    CraftCategory,
    CraftEnchantSetPlan,
    CraftMode,
    Disposition,
    EnchantMode,
    EnchantmentOption,
    HolyOilItem,
    HolyOilMode,
    HolyOilOption,
    InventorySetOption,
} from "./batch-crafting-types";

export default interface BatchCraftingSectionState {
    status: BatchCraftingStatus | null;
    batchType: BatchType;
    disposition: Disposition;
    message: string;
    isSaving: boolean;
    holyOilItems: HolyOilItem[];
    holyOilOptions: HolyOilOption[];
    selectedItems: number[];
    selectedOils: number[];
    holyOilsLoading: boolean;
    holyOilMode: HolyOilMode;
    craftMode: CraftMode;
    alchemyMode: AlchemyMode;
    craftCategory: CraftCategory;
    weaponType: string;
    armourType: string;
    specificItemId: number | null;
    craftAmount: number | "";
    craftableItems: CraftableItem[];
    alchemyItems: CraftableItem[];
    selectedAlchemyItemId: number | null;
    enchantments: EnchantmentOption[];
    selectedPrefixId: number | null;
    selectedSuffixId: number | null;
    enchantMode: EnchantMode;
    inventorySets: InventorySetOption[];
    inventorySetsLoading: boolean;
    selectedSetId: number | null;
    craftEnchantSetPlan: CraftEnchantSetPlan;
    craftEnchantSetBulkPrefixId: number | null;
    craftEnchantSetBulkSuffixId: number | null;
    preview: BatchCraftingPreview | null;
    previewLoading: boolean;
    previewError: string | null;
}
