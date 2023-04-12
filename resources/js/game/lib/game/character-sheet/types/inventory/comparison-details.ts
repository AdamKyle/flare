import InventoryComparisonAdjustment from "../../../../../sections/components/item-details/comparison/types/inventory-comparison-adjustment";

export default interface ComparisonDetails {

    characterId: number;

    bowEquipped: boolean;

    hammerEquipped: boolean;

    setEquipped: boolean;

    setIndex: number;

    slotId: number;

    slotPosition: string | null;

    staveEquipped: boolean;

    type: string;

    itemToEquip: InventoryComparisonAdjustment;

    details: InventoryComparisonAdjustment[] | [];
}
