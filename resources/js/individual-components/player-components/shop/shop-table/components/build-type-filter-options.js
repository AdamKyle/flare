import { ItemType } from "../../../../../game/components/items/enums/item-type";
import { startCase } from "lodash";
export var ITEM_TYPES = [
    ItemType.WEAPON,
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
export var itemTypeFilter = function () {
    return ITEM_TYPES.map(function (type) {
        return {
            label: startCase(type),
            value: type,
        };
    });
};
//# sourceMappingURL=build-type-filter-options.js.map
