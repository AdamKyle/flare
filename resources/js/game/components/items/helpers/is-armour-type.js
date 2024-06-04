import { ItemType } from "../enums/item-type";
var ARMOUR_TYPES = [
    ItemType.BODY,
    ItemType.BOOTS,
    ItemType.GLOVES,
    ItemType.HELMET,
    ItemType.LEGGINGS,
    ItemType.SLEEVES,
    ItemType.SHIELD,
];
export var isArmourType = function (itemType) {
    return ARMOUR_TYPES.includes(itemType);
};
//# sourceMappingURL=is-armour-type.js.map
