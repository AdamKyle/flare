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
import React, { Fragment } from "react";
import Table from "../../../../../components/ui/data-tables/table";
import { buildLimitedColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import ListItemModal from "../../../../../components/modals/item-details/action-modals/list-item-modal";
import Ajax from "../../../../../lib/ajax/ajax";
import InventoryUseItem from "../../modals/inventory-use-item";
import InventoryUseDetails from "../../modals/inventory-use-details";
import InventoryActionConfirmationModal from "../../modals/inventory-action-confirmation-modal";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import SuccessButton from "../../../../../components/ui/buttons/success-button";
var UsableItemsTable = (function (_super) {
    __extends(UsableItemsTable, _super);
    function UsableItemsTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_list_modal: false,
            show_use_item_modal: false,
            show_usable_details: false,
            show_use_many: false,
            show_destroy_item: false,
            item_to_list: null,
            item_to_use: null,
            item_to_destroy_name: null,
            item_slot_id_to_delete: null,
        };
        return _this;
    }
    UsableItemsTable.prototype.manageUseItem = function (row) {
        this.setState({
            show_use_item_modal: !this.state.show_use_item_modal,
            item_to_use: row,
        });
    };
    UsableItemsTable.prototype.list = function (listedFor) {
        var _this = this;
        var _a;
        new Ajax()
            .setRoute("market-board/sell-item/" + this.props.character_id)
            .setParameters({
                list_for: listedFor,
                slot_id:
                    (_a = this.state.item_to_list) === null || _a === void 0
                        ? void 0
                        : _a.slot_id,
            })
            .doAjaxCall(
                "post",
                function (result) {
                    _this.setState(
                        {
                            item_to_list: null,
                        },
                        function () {
                            _this.props.update_inventory(result.data.inventory);
                            _this.props.set_success_message(
                                result.data.message,
                            );
                        },
                    );
                },
                function (error) {},
            );
    };
    UsableItemsTable.prototype.destroy = function (row) {
        this.setState({
            show_destroy_item: !this.state.show_destroy_item,
            item_to_destroy_name: row.item_name,
            item_slot_id_to_delete: row.slot_id,
        });
    };
    UsableItemsTable.prototype.showDestroyConfirmation = function () {
        this.setState({
            show_destroy_item: !this.state.show_destroy_item,
            item_to_destroy_name: null,
            item_slot_id_to_delete: null,
        });
    };
    UsableItemsTable.prototype.manageList = function (item) {
        this.setState({
            show_list_modal: !this.state.show_list_modal,
            item_to_list: typeof item !== "undefined" ? item : null,
        });
    };
    UsableItemsTable.prototype.actions = function (row) {
        var _this = this;
        return React.createElement(
            "div",
            { className: "flex flex-col w-full" },
            React.createElement(PrimaryButton, {
                button_label: "List",
                on_click: function () {
                    return _this.manageList(row);
                },
                additional_css: "mt-3 mb-2",
            }),
            React.createElement(DangerButton, {
                button_label: "Destroy",
                on_click: function () {
                    return _this.destroy(row);
                },
            }),
            row.usable && !row.damages_kingdoms
                ? React.createElement(SuccessButton, {
                      button_label: "Use Item",
                      on_click: function () {
                          return _this.manageUseItem(row);
                      },
                      additional_css: "my-3",
                  })
                : null,
        );
    };
    UsableItemsTable.prototype.manageViewItem = function (item) {
        this.setState({
            show_usable_details: !this.state.show_usable_details,
            item_to_use: typeof item !== "undefined" ? item : null,
        });
    };
    UsableItemsTable.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "max-w-full overflow-y-hidden" },
                React.createElement(Table, {
                    data: this.props.usable_items,
                    columns: buildLimitedColumns(
                        this.props.view_port,
                        this,
                        this.manageViewItem.bind(this),
                        true,
                    ),
                    dark_table: this.props.dark_table,
                }),
            ),
            this.state.show_destroy_item
                ? React.createElement(
                      InventoryActionConfirmationModal,
                      {
                          is_open: this.state.show_destroy_item,
                          manage_modal: this.showDestroyConfirmation.bind(this),
                          title: "Destroy " + this.state.item_to_destroy_name,
                          url:
                              "character/" +
                              this.props.character_id +
                              "/inventory/destroy-alchemy-item",
                          ajax_params: {
                              slot_id: this.state.item_slot_id_to_delete,
                          },
                          update_inventory: this.props.update_inventory,
                          set_success_message: this.props.set_success_message,
                      },
                      React.createElement(
                          "p",
                          null,
                          "Are you sure you want to do this? This action will destroy the selected item from your usable inventory. You cannot undo this action.",
                      ),
                  )
                : null,
            this.state.show_list_modal && this.state.item_to_list !== null
                ? React.createElement(ListItemModal, {
                      is_open: this.state.show_list_modal,
                      manage_modal: this.manageList.bind(this),
                      list_item: this.list.bind(this),
                      item: this.state.item_to_list,
                      dark_charts: this.props.dark_table,
                  })
                : null,
            this.state.show_use_item_modal && this.state.item_to_use !== null
                ? React.createElement(InventoryUseItem, {
                      is_open: this.state.show_use_item_modal,
                      manage_modal: this.manageUseItem.bind(this),
                      item: this.state.item_to_use,
                      update_inventory: this.props.update_inventory,
                      set_success_message: this.props.set_success_message,
                      character_id: this.props.character_id,
                  })
                : null,
            this.state.show_usable_details && this.state.item_to_use !== null
                ? React.createElement(InventoryUseDetails, {
                      is_open: this.state.show_usable_details,
                      manage_modal: this.manageViewItem.bind(this),
                      item: this.state.item_to_use,
                  })
                : null,
        );
    };
    return UsableItemsTable;
})(React.Component);
export default UsableItemsTable;
//# sourceMappingURL=usable-items-table.js.map
