import InventoryComparisonAdjustment from "../modal/inventory-comparison-adjustment";

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
