import {
    AlchemyMode,
    BatchCraftingStatus,
    BatchType,
    CraftableItem,
    CraftCategory,
    CraftMode,
    Disposition,
    EnchantmentOption,
    HolyOilItem,
    HolyOilOption,
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
    trinketryIsMaxed: boolean;
    enchantments: EnchantmentOption[];
    selectedPrefixId: number | null;
    selectedSuffixId: number | null;
}
