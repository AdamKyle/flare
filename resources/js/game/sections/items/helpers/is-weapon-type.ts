import {ItemType} from "../enums/item-type";

const WEAPON_TYPES = [
    ItemType.WEAPON,
    ItemType.BOW,
    ItemType.FAN,
    ItemType.GUN,
    ItemType.HAMMER,
    ItemType.STAVE,
];

export const isWeaponType = (itemType: ItemType) => {
    return WEAPON_TYPES.includes(itemType);
}
