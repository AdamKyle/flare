import { ItemType } from "../enums/item-type";
var WEAPON_TYPES = [
    ItemType.WEAPON,
    ItemType.BOW,
    ItemType.FAN,
    ItemType.GUN,
    ItemType.HAMMER,
    ItemType.STAVE,
    ItemType.SPELL_DAMAGE,
];
export var isWeaponType = function (itemType) {
    return WEAPON_TYPES.includes(itemType);
};
//# sourceMappingURL=is-weapon-type.js.map
