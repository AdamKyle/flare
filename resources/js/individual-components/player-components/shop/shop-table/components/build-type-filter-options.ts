import { ItemType } from "../../../../../game/components/items/enums/item-type";
import { startCase } from "lodash";

export const ITEM_TYPES = [
    ItemType.CLASS_SPECIFIC,
    ItemType.HAMMER,
    ItemType.FAN,
    ItemType.BOW,
    ItemType.GUN,
    ItemType.SCRATCH_AWL,
    ItemType.STAVE,
    ItemType.SHIELD,
    ItemType.MACE,
    ItemType.BODY,
    ItemType.BOOTS,
    ItemType.GLOVES,
    ItemType.SLEEVES,
    ItemType.LEGGINGS,
    ItemType.HELMET,
    ItemType.SPELL_DAMAGE,
    ItemType.SPELL_HEALING,
    ItemType.RING,
];

export const itemTypeFilter = (): { label: string; value: ItemType }[] => {
    return ITEM_TYPES.map((type: ItemType) => {
        let label = startCase(type);

        if (type === ItemType.CLASS_SPECIFIC) {
            label = "For your class";
        }

        return {
            label: label,
            value: type,
        };
    });
};
