var __read =
    (this && this.__read) ||
    function (o, n) {
        var m = typeof Symbol === "function" && o[Symbol.iterator];
        if (!m) return o;
        var i = m.call(o),
            r,
            ar = [],
            e;
        try {
            while ((n === void 0 || n-- > 0) && !(r = i.next()).done)
                ar.push(r.value);
        } catch (error) {
            e = { error: error };
        } finally {
            try {
                if (r && !r.done && (m = i["return"])) m.call(i);
            } finally {
                if (e) throw e.error;
            }
        }
        return ar;
    };
var __spreadArray =
    (this && this.__spreadArray) ||
    function (to, from, pack) {
        if (pack || arguments.length === 2)
            for (var i = 0, l = from.length, ar; i < l; i++) {
                if (ar || !(i in from)) {
                    if (!ar) ar = Array.prototype.slice.call(from, 0, i);
                    ar[i] = from[i];
                }
            }
        return to.concat(ar || Array.prototype.slice.call(from));
    };
import React from "react";
import { formatNumber } from "../../../../../game/lib/game/format-number";
import { ItemType } from "../../../../../game/components/items/enums/item-type";
import PrimaryLinkButton from "../../../../../game/components/ui/buttons/primary-link-button";
import PrimaryButton from "../../../../../game/components/ui/buttons/primary-button";
import SuccessButton from "../../../../../game/components/ui/buttons/success-button";
import { shopServiceContainer } from "../../container/shop-container";
import ShopAjax, { SHOP_ACTIONS } from "../../ajax/shop-ajax";
var ShopTableColumns = (function () {
    function ShopTableColumns() {
        this.WEAPON_TYPES = [
            ItemType.WEAPON,
            ItemType.BOW,
            ItemType.FAN,
            ItemType.GUN,
            ItemType.HAMMER,
            ItemType.STAVE,
        ];
        this.ARMOUR_TYPES = [
            ItemType.BODY,
            ItemType.BOOTS,
            ItemType.GLOVES,
            ItemType.HELMET,
            ItemType.LEGGINGS,
            ItemType.SLEEVES,
            ItemType.SHIELD,
        ];
        this.ajax = shopServiceContainer().fetch(ShopAjax);
    }
    ShopTableColumns.prototype.setComponent = function (component) {
        this.component = component;
        return this;
    };
    ShopTableColumns.prototype.viewPurchaseAny = function (item, viewBuyMany) {
        return viewBuyMany(item);
    };
    ShopTableColumns.prototype.viewComparison = function (
        item,
        viewComparison,
    ) {
        return viewComparison(item);
    };
    ShopTableColumns.prototype.buildColumns = function (
        onClick,
        viewBuyMany,
        viewComparison,
        itemType,
    ) {
        var _this = this;
        var shopColumns = [
            {
                name: "Name",
                selector: function (row) {
                    return row.name;
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        null,
                        React.createElement(PrimaryLinkButton, {
                            button_label: row.name,
                            on_click: function () {
                                return onClick(row.id);
                            },
                            additional_css:
                                "text-gray-600 hover:text-gray-700 dark:text-gray-300 dark:hover:text-gray-400",
                        }),
                    );
                },
            },
            {
                name: "Type",
                selector: function (row) {
                    return row.type;
                },
                sortable: true,
            },
        ];
        if (typeof itemType === "undefined") {
            shopColumns = __spreadArray(
                __spreadArray(
                    __spreadArray([], __read(shopColumns), false),
                    __read(this.getWeaponColumns()),
                    false,
                ),
                __read(this.getArmourColumns()),
                false,
            );
        } else {
            var isWeaponType =
                this.WEAPON_TYPES.filter(function (weaponType) {
                    return weaponType === itemType;
                }).length > 0;
            var isArmorType =
                this.ARMOUR_TYPES.filter(function (armorType) {
                    return armorType === itemType;
                }).length > 0;
            if (isWeaponType) {
                shopColumns = __spreadArray(
                    __spreadArray([], __read(shopColumns), false),
                    __read(this.getWeaponColumns()),
                    false,
                );
            }
            if (isArmorType) {
                shopColumns = __spreadArray(
                    __spreadArray([], __read(shopColumns), false),
                    __read(this.getArmourColumns()),
                    false,
                );
            }
        }
        shopColumns.push({
            name: "Cost",
            selector: function (row) {
                return row.cost;
            },
            sortable: true,
            format: function (row) {
                return formatNumber(row.cost);
            },
        });
        shopColumns = __spreadArray(
            __spreadArray([], __read(shopColumns), false),
            [
                {
                    name: "Actions",
                    selector: function (row) {
                        return row.name;
                    },
                    cell: function (row) {
                        return React.createElement(
                            "div",
                            { className: "my-2" },
                            React.createElement(
                                "div",
                                { className: "w-full mb-2" },
                                React.createElement(PrimaryButton, {
                                    button_label: "Buy",
                                    on_click: function () {
                                        return _this.buyItem(row);
                                    },
                                    additional_css: "w-full",
                                }),
                            ),
                            React.createElement(
                                "div",
                                { className: "w-full mb-2" },
                                React.createElement(PrimaryButton, {
                                    button_label: "Compare",
                                    on_click: function () {
                                        return _this.viewComparison(
                                            row,
                                            viewComparison,
                                        );
                                    },
                                    additional_css: "w-full",
                                }),
                            ),
                            React.createElement(
                                "div",
                                { className: "w-full" },
                                React.createElement(SuccessButton, {
                                    button_label: "Buy Multiple",
                                    on_click: function () {
                                        return _this.viewPurchaseAny(
                                            row,
                                            viewBuyMany,
                                        );
                                    },
                                    additional_css: "w-full",
                                }),
                            ),
                        );
                    },
                },
            ],
            false,
        );
        return shopColumns;
    };
    ShopTableColumns.prototype.getWeaponColumns = function () {
        return [
            {
                name: "Attack",
                selector: function (row) {
                    return row.base_damage;
                },
                sortable: true,
                format: function (row) {
                    return formatNumber(row.base_damage);
                },
            },
        ];
    };
    ShopTableColumns.prototype.getArmourColumns = function () {
        return [
            {
                name: "Attack",
                selector: function (row) {
                    return row.base_ac;
                },
                sortable: true,
                format: function (row) {
                    return formatNumber(row.base_ac);
                },
            },
        ];
    };
    ShopTableColumns.prototype.buyItem = function (row) {
        var _this = this;
        if (typeof this.component !== "undefined") {
            this.component.setState(
                {
                    error_message: null,
                    success_message: null,
                },
                function () {
                    if (typeof _this.component === "undefined") {
                        return;
                    }
                    _this.ajax.doShopAction(_this.component, SHOP_ACTIONS.BUY, {
                        item_id: row.id,
                    });
                },
            );
        }
    };
    return ShopTableColumns;
})();
export default ShopTableColumns;
//# sourceMappingURL=shop-table-columns.js.map
