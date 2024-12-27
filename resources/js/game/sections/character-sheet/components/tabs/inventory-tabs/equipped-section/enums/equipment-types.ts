import { ItemForColorizationDefinition } from "../../../../../../../components/items/item-name/types/item-name-coloration-text-props";
import ItemDetails from "../../../../modals/components/item-details";
import InventoryDetails from "../../../../../../../lib/game/character-sheet/types/inventory/inventory-details";

export enum EquipmentTypes {
    NORMAL = "normal",
    ONE_ENCHANT = "one_enchant",
    TWO_ENCHANTS = "two_enchants",
    HOLY = "holy",
    UNIQUE = "unique",
    MYTHICAL = "mythical",
    COSMIC = "cosmic",
}

export const determineEquipmentType = (
    item: InventoryDetails,
): EquipmentTypes => {
    if (item.is_cosmic) {
        return EquipmentTypes.COSMIC;
    }

    if (item.is_mythic) {
        return EquipmentTypes.MYTHICAL;
    }

    if (item.is_unique) {
        return EquipmentTypes.UNIQUE;
    }

    if (item.has_holy_stacks_applied > 0) {
        return EquipmentTypes.HOLY;
    }

    if (item.attached_affixes_count > 1) {
        return EquipmentTypes.TWO_ENCHANTS;
    }

    if (item.attached_affixes_count > 0) {
        return EquipmentTypes.ONE_ENCHANT;
    }

    return EquipmentTypes.NORMAL;
};
