var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
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
import React, { Fragment } from "react";
import Table from "../../../../../components/ui/data-tables/table";
import { BuildInventoryTableColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import ItemDetailsModal from "../../../../../components/modals/item-details/item-details-modal";
var InventoryTable = (function (_super) {
    __extends(InventoryTable, _super);
    function InventoryTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            view_comparison: false,
            slot_id: 0,
            item_type: "",
            selected_slots: [],
        };
        return _this;
    }
    InventoryTable.prototype.viewItem = function (item) {
        this.setState({
            view_comparison: true,
            slot_id: typeof item !== "undefined" ? item.slot_id : 0,
            item_type: typeof item !== "undefined" ? item.type : "",
        });
    };
    InventoryTable.prototype.manageSelectedItems = function (e) {
        var _this = this;
        var isChecked = e.target.checked;
        var slotId = parseInt(e.target.dataset.slotId, 10) || 0;
        if (slotId <= 0) {
            return;
        }
        var selected_slots = this.state.selected_slots;
        var updatedSlots;
        if (selected_slots.length > 0) {
            var duplicateId = selected_slots.indexOf(slotId);
            if (isChecked && duplicateId !== -1) {
                return;
            }
            updatedSlots = isChecked
                ? __spreadArray(
                      __spreadArray([], __read(selected_slots), false),
                      [slotId],
                      false,
                  )
                : selected_slots.filter(function (id) {
                      return id !== slotId;
                  });
        } else {
            updatedSlots = [slotId];
        }
        this.setState(
            {
                selected_slots: updatedSlots,
            },
            function () {
                _this.props.manage_selected_items(updatedSlots);
            },
        );
    };
    InventoryTable.prototype.closeViewItem = function () {
        this.setState({
            view_comparison: false,
            slot_id: 0,
            item_type: "",
        });
    };
    InventoryTable.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                InfoAlert,
                { additional_css: "mt-4 mb-4" },
                'Click the item name to get additional actions. This table only sometimes updates automatically, such as with mass disenchanting items. Players will find their inventory fills up with a lot of "colorful" items, you can learn more about that',
                " ",
                React.createElement(
                    "a",
                    { href: "/information/equipment-types", target: "_blank" },
                    "here. ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
            React.createElement(
                "div",
                { className: "max-w-full overflow-x-hidden" },
                React.createElement(Table, {
                    data: this.props.inventory,
                    columns: BuildInventoryTableColumns(
                        this.props.view_port,
                        undefined,
                        this.viewItem.bind(this),
                        this.props.manage_skills,
                        undefined,
                        this.manageSelectedItems.bind(this),
                    ),
                    dark_table: this.props.dark_table,
                }),
            ),
            this.state.view_comparison
                ? React.createElement(ItemDetailsModal, {
                      is_open: this.state.view_comparison,
                      manage_modal: this.closeViewItem.bind(this),
                      slot_id: this.state.slot_id,
                      character_id: this.props.character_id,
                      update_inventory: this.props.update_inventory,
                      set_success_message: this.props.set_success_message,
                      is_dead: this.props.is_dead,
                      is_automation_running: this.props.is_automation_running,
                  })
                : null,
        );
    };
    return InventoryTable;
})(React.Component);
export default InventoryTable;
//# sourceMappingURL=inventory-table.js.map
