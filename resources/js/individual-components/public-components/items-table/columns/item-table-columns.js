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
import PrimaryLinkButton from "../../../../game/components/ui/buttons/primary-link-button";
import { formatNumber } from "../../../../game/lib/game/format-number";
import { TableType } from "../types/table-type";
var ItemTableColumns = (function () {
    function ItemTableColumns() {}
    ItemTableColumns.prototype.buildColumns = function (onClick, tableType) {
        var itemsTableColumns = [
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
        itemsTableColumns = __spreadArray(
            __spreadArray(
                __spreadArray(
                    __spreadArray([], __read(itemsTableColumns), false),
                    __read(this.getWeaponColumns()),
                    false,
                ),
                __read(this.getArmourColumns()),
                false,
            ),
            __read(this.getHealingColumns()),
            false,
        );
        if (tableType === TableType.CRAFTING) {
            itemsTableColumns.push({
                name: "Cost (Gold)",
                selector: function (row) {
                    return formatNumber(row.cost);
                },
                sortable: true,
            });
            itemsTableColumns.push({
                name: "Crafting Type",
                selector: function (row) {
                    return row.crafting_type;
                },
                sortable: true,
            });
            itemsTableColumns.push({
                name: "Skill Level Required",
                selector: function (row) {
                    return row.skill_level_req;
                },
                sortable: true,
            });
            itemsTableColumns.push({
                name: "Skill Level Trivial",
                selector: function (row) {
                    return row.skill_level_trivial;
                },
                sortable: true,
            });
        }
        return itemsTableColumns;
    };
    ItemTableColumns.prototype.getWeaponColumns = function () {
        return [
            {
                name: "Attack",
                selector: function (row) {
                    return row.raw_damage;
                },
                sortable: true,
                format: function (row) {
                    return formatNumber(row.raw_damage);
                },
            },
        ];
    };
    ItemTableColumns.prototype.getHealingColumns = function () {
        return [
            {
                name: "Healing",
                selector: function (row) {
                    return row.raw_healing;
                },
                sortable: true,
                format: function (row) {
                    return formatNumber(row.raw_healing);
                },
            },
        ];
    };
    ItemTableColumns.prototype.getArmourColumns = function () {
        return [
            {
                name: "AC",
                selector: function (row) {
                    return row.raw_ac;
                },
                sortable: true,
                format: function (row) {
                    return formatNumber(row.raw_ac);
                },
            },
        ];
    };
    return ItemTableColumns;
})();
export default ItemTableColumns;
//# sourceMappingURL=item-table-columns.js.map
