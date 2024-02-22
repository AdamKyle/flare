import {ItemType} from "../enums/item-type";

const ARMOUR_TYPES = [
    ItemType.BODY,
    ItemType.BOOTS,
    ItemType.GLOVES,
    ItemType.HELMET,
    ItemType.LEGGINGS,
    ItemType.SLEEVES,
    ItemType.SHIELD,
];

export const isArmourType = (itemType: ItemType) => {
    return ARMOUR_TYPES.includes(itemType);
}
