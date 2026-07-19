import {
    AlchemyMode,
    BatchCraftingPreview,
    BatchCraftingStatus,
    BatchType,
    CraftableItem,
    CraftCategory,
    CraftEnchantSetPlan,
    CraftSetPlan,
    CraftMode,
    Disposition,
    EnchantMode,
    EnchantmentOption,
    HolyOilItem,
    HolyOilMode,
    HolyOilOption,
    InventorySetOption,
    OutputDestination,
} from "./batch-crafting-types";

export default interface BatchCraftingSectionState {
    status: BatchCraftingStatus | null;
    statusLoading: boolean;
    statusLoadError: string | null;
    batchType: BatchType;
    disposition: Disposition;
    listingPrice: number | "";
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
    craftableItemsLoading: boolean;
    alchemyItems: CraftableItem[];
    alchemyItemsLoading: boolean;
    selectedAlchemyItemId: number | null;
    enchantments: EnchantmentOption[];
    enchantmentsLoading: boolean;
    selectedPrefixId: number | null;
    selectedSuffixId: number | null;
    enchantMode: EnchantMode;
    inventorySets: InventorySetOption[];
    inventorySetsLoading: boolean;
    selectedSetId: number | null;
    outputDestination: OutputDestination;
    outputSetId: number | null;
    craftEnchantSetMode: "build_new";
    craftEnchantSetPlan: CraftEnchantSetPlan;
    craftEnchantSetBulkPrefixId: number | null;
    craftEnchantSetBulkSuffixId: number | null;
    craftSetPlan: CraftSetPlan;
    preview: BatchCraftingPreview | null;
    previewLoading: boolean;
    previewError: string | null;
    hideMaxedCraftNotice: boolean;
    affixDetailsModalAffix: EnchantmentOption | null;
    craftEnchantSetItemDetailsModalItem: any | null;
    listingChartData: any[];
    listingChartLoading: boolean;
    listingChartItemId: number | null;
}
