import ItemToEquip from "./item-to-equip";
import ItemAtonement from "./item-atonement";
import ComparisonDetails from "./comparison-details";

export interface ComparisonData {
    details: ComparisonDetails[] | [];
    atonement: ItemAtonement;
    itemToEquip: ItemToEquip;
    type: string;
    slotId: number;
    slotPosition: string | null;
    characterId: number;
    bowEquipped: boolean;
    hammerEquipped: boolean;
    staveEquipped: boolean;
    setEquipped: boolean;
    setIndex: number | null;
    setName: string | null;
}
