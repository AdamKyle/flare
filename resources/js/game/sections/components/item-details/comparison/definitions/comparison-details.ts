import InventoryComparisonAdjustment from "./inventory-comparison-adjustment";
import OriginalAtonement from "../../../../../lib/game/types/core/atonement/definitions/original-atonement";

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

    atonement: Atonement;
}

interface Atonement {
    inventory_atonements: inventoryAtonement[];
    item_atonement: OriginalAtonement;
}

interface inventoryAtonement {
    data: OriginalAtonement;
    item_name: string;
}
