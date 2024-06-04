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
import DropDown from "../../../../components/ui/drop-down/drop-down";
import InventoryTable from "./inventory-tabs/inventory-table";
import UsableItemsTable from "./inventory-tabs/usable-items-table";
import { isEqual } from "lodash";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import clsx from "clsx";
import InventoryUseManyItems from "../modals/inventory-use-many-items";
import { GemBagTable } from "./inventory-tabs/gem-bag-table";
import { InventoryActionConfirmationType } from "../../../../components/character-sheet/inventory-action-confirmation-modal/helpers/enums/inventory-action-confirmation-type";
import BaseInventoryActionConfirmationModal from "../../../../components/character-sheet/inventory-action-confirmation-modal/modals/base-inventory-action-confirmation-modal";
import ModalPropsBuilder from "../../../../components/character-sheet/inventory-action-confirmation-modal/helpers/modal-props-builder";
import { serviceContainer } from "../../../../lib/containers/core-container";
var InventoryTabSection = (function (_super) {
    __extends(InventoryTabSection, _super);
    function InventoryTabSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            table: "inventory",
            data: _this.props.inventory,
            usable_items: _this.props.usable_items,
            show_action_confirmation_modal: false,
            action_confirmation_type: null,
            show_destroy_all: false,
            show_disenchant_all: false,
            show_sell_all: false,
            show_use_many: false,
            show_destroy_all_alchemy: false,
            show_equip_best: false,
            success_message: null,
            search_string: "",
            selected_items: [],
        };
        _this.modalPropsBuilder = serviceContainer().fetch(ModalPropsBuilder);
        return _this;
    }
    InventoryTabSection.prototype.componentDidUpdate = function () {
        if (
            !isEqual(this.state.data, this.props.inventory) &&
            this.state.search_string.length === 0
        ) {
            this.setState({
                data: this.props.inventory,
            });
        }
        if (
            !isEqual(this.state.usable_items, this.props.usable_items) &&
            this.state.search_string.length === 0
        ) {
            this.setState({
                usable_items: this.props.usable_items,
            });
        }
    };
    InventoryTabSection.prototype.setSuccessMessage = function (message) {
        this.setState({
            success_message: message,
        });
    };
    InventoryTabSection.prototype.switchTable = function (type) {
        this.setState({
            table: type,
        });
    };
    InventoryTabSection.prototype.search = function (e) {
        var value = e.target.value;
        if (this.state.table === "Inventory") {
            this.setState({
                data: this.props.inventory
                    .filter(function (item) {
                        var itemName = item.item_name.toLowerCase();
                        var itemType = item.type.toLowerCase();
                        if (
                            itemName.includes(value.toLowerCase()) ||
                            itemType.includes(value.toLowerCase())
                        ) {
                            return item;
                        }
                    })
                    .filter(function (item) {
                        return item;
                    }),
                search_string: value,
            });
        } else {
            this.setState({
                usable_items: this.props.usable_items
                    .filter(function (item) {
                        var itemName = item.item_name.toLowerCase();
                        if (itemName.includes(value.toLowerCase())) {
                            return item;
                        }
                    })
                    .filter(function (item) {
                        return item;
                    }),
                search_string: value,
            });
        }
    };
    InventoryTabSection.prototype.manageDisenchantAll = function () {
        this.setState({
            show_disenchant_all: !this.state.show_disenchant_all,
        });
    };
    InventoryTabSection.prototype.manageConfirmationModal = function (type) {
        var actionConfirmationType = null;
        if (!this.state.show_action_confirmation_modal && type) {
            actionConfirmationType = type;
        }
        this.setState({
            show_action_confirmation_modal:
                !this.state.show_action_confirmation_modal,
            action_confirmation_type: actionConfirmationType,
        });
    };
    InventoryTabSection.prototype.manageDestroyAllAlchemy = function () {
        this.setState({
            show_destroy_all_alchemy: !this.state.show_destroy_all_alchemy,
        });
    };
    InventoryTabSection.prototype.manageSellAll = function () {
        this.setState({
            show_sell_all: !this.state.show_sell_all,
        });
    };
    InventoryTabSection.prototype.manageUseManyItems = function () {
        this.setState({
            show_use_many: !this.state.show_use_many,
        });
    };
    InventoryTabSection.prototype.closeSuccess = function () {
        this.setState({
            success_message: null,
        });
    };
    InventoryTabSection.prototype.createActionsDropDown = function () {
        var _this = this;
        if (this.state.table === "inventory") {
            return [
                {
                    name: "Destroy All",
                    icon_class: "far fa-trash-alt",
                    on_click: function () {
                        return _this.manageConfirmationModal(
                            InventoryActionConfirmationType.DESTROY_ALL,
                        );
                    },
                },
                {
                    name: "Disenchant All",
                    icon_class: "ra ra-fire",
                    on_click: function () {
                        return _this.manageConfirmationModal(
                            InventoryActionConfirmationType.DISENCHANT_ALL,
                        );
                    },
                },
                {
                    name: "Sell All",
                    icon_class: "far fa-money-bill-alt",
                    on_click: function () {
                        return _this.manageConfirmationModal(
                            InventoryActionConfirmationType.SELL_ALL,
                        );
                    },
                },
            ];
        }
        return [
            {
                name: "Use many",
                icon_class: "ra ra-bottle-vapors",
                on_click: function () {
                    return _this.manageUseManyItems();
                },
            },
            {
                name: "Destroy All",
                icon_class: "far fa-trash-alt",
                on_click: function () {
                    return _this.manageConfirmationModal(
                        InventoryActionConfirmationType.DESTROY_ALL_ALCHEMY_ITEMS,
                    );
                },
            },
        ];
    };
    InventoryTabSection.prototype.createMultiSelectedItemDropDown =
        function () {
            var _this = this;
            return [
                {
                    name: "Equip Selected",
                    icon_class: "far fa-trash-alt",
                    on_click: function () {
                        return _this.manageConfirmationModal(
                            InventoryActionConfirmationType.EQUIP_SELECTED,
                        );
                    },
                },
                {
                    name: "Move Selected",
                    icon_class: "fas fa-truck-loading",
                    on_click: function () {
                        return _this.manageConfirmationModal(
                            InventoryActionConfirmationType.MOVE_SELECTED,
                        );
                    },
                },
                {
                    name: "Destroy Selected",
                    icon_class: "far fa-trash-alt",
                    on_click: function () {
                        return _this.manageConfirmationModal(
                            InventoryActionConfirmationType.DESTROY_SELECTED,
                        );
                    },
                },
                {
                    name: "Disenchant Selected",
                    icon_class: "ra ra-fire",
                    on_click: function () {
                        return _this.manageConfirmationModal(
                            InventoryActionConfirmationType.DISENCHANT_SELECTED,
                        );
                    },
                },
                {
                    name: "Sell Selected",
                    icon_class: "far fa-money-bill-alt",
                    on_click: function () {
                        return _this.manageConfirmationModal(
                            InventoryActionConfirmationType.SELL_SELECTED,
                        );
                    },
                },
            ];
        };
    InventoryTabSection.prototype.isDropDownHidden = function () {
        if (this.state.table === "inventory") {
            return this.state.data.length === 0;
        } else {
            return this.props.usable_items.length === 0;
        }
    };
    InventoryTabSection.prototype.isSelectedDropDownHidden = function () {
        return this.state.selected_items.length <= 0;
    };
    InventoryTabSection.prototype.updateInventory = function (inventory) {
        var _this = this;
        this.setState(
            {
                search_string: "",
            },
            function () {
                _this.props.update_inventory(inventory);
            },
        );
    };
    InventoryTabSection.prototype.setSelectedItems = function (selectedItems) {
        var _this = this;
        if (this.state.table !== "inventory") {
            return;
        }
        if (this.state.table !== "inventory") {
            return;
        }
        var filteredSelectedItems = selectedItems
            .map(function (slotId) {
                var foundItem = _this.state.data.find(function (slot) {
                    return slot.slot_id === slotId;
                });
                if (typeof foundItem !== "undefined") {
                    return {
                        item_name: foundItem.item_name,
                        slot_id: foundItem.slot_id,
                    };
                }
                return null;
            })
            .filter(function (item) {
                return item !== null;
            });
        this.setState({
            selected_items: filteredSelectedItems,
        });
    };
    InventoryTabSection.prototype.renderTables = function () {
        switch (this.state.table) {
            case "inventory":
                return React.createElement(InventoryTable, {
                    dark_table: this.props.dark_tables,
                    character_id: this.props.character_id,
                    inventory: this.state.data,
                    is_dead: this.props.is_dead,
                    update_inventory: this.updateInventory.bind(this),
                    usable_sets: this.props.usable_sets,
                    set_success_message: this.setSuccessMessage.bind(this),
                    is_automation_running: this.props.is_automation_running,
                    manage_skills: this.props.manage_skills,
                    manage_selected_items: this.setSelectedItems.bind(this),
                    view_port: this.props.view_port,
                });
            case "usable-items":
                return React.createElement(UsableItemsTable, {
                    dark_table: this.props.dark_tables,
                    character_id: this.props.character_id,
                    usable_items: this.state.usable_items,
                    is_dead: this.props.is_dead,
                    update_inventory: this.updateInventory.bind(this),
                    set_success_message: this.setSuccessMessage.bind(this),
                    is_automation_running: this.props.is_automation_running,
                    view_port: this.props.view_port,
                });
            case "gems":
                return React.createElement(GemBagTable, {
                    dark_table: this.props.dark_tables,
                    character_id: this.props.character_id,
                    is_dead: this.props.is_dead,
                });
            default:
                return null;
        }
    };
    InventoryTabSection.prototype.getSelectedNames = function () {
        return this.state.selected_items.map(function (selectedItem) {
            return selectedItem.item_name;
        });
    };
    InventoryTabSection.prototype.render = function () {
        var _this = this;
        var modalPropsBuilder = null;
        if (this.state.action_confirmation_type !== null) {
            modalPropsBuilder = this.modalPropsBuilder.setActionType(
                this.state.action_confirmation_type,
            );
        }
        return React.createElement(
            Fragment,
            null,
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      {
                          close_alert: this.closeSuccess.bind(this),
                          additional_css: "mt-4 mb-4",
                      },
                      this.state.success_message,
                  )
                : null,
            React.createElement(
                "div",
                { className: "flex flex-row flex-wrap items-center" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(DropDown, {
                        menu_items: [
                            {
                                name: "Inventory",
                                icon_class: "fas fa-shopping-bag",
                                on_click: function () {
                                    return _this.switchTable("inventory");
                                },
                            },
                            {
                                name: "Usable",
                                icon_class: "ra ra-bubbling-potion",
                                on_click: function () {
                                    return _this.switchTable("usable-items");
                                },
                            },
                            {
                                name: "Gem Bag",
                                icon_class: "fas fa-gem",
                                on_click: function () {
                                    return _this.switchTable("gems");
                                },
                            },
                        ],
                        button_title: "Type",
                        selected_name: this.state.table,
                        disabled: this.props.is_dead,
                    }),
                ),
                React.createElement(
                    "div",
                    {
                        className: clsx("ml-2", {
                            hidden: this.isDropDownHidden(),
                        }),
                    },
                    React.createElement(DropDown, {
                        menu_items: this.createActionsDropDown(),
                        button_title: "Actions",
                        selected_name: this.state.table,
                        disabled: this.props.is_dead,
                    }),
                ),
                React.createElement(
                    "div",
                    {
                        className: clsx("ml-2", {
                            hidden: this.isSelectedDropDownHidden(),
                        }),
                    },
                    React.createElement(DropDown, {
                        menu_items: this.createMultiSelectedItemDropDown(),
                        button_title: "Selected Items (Actions)",
                        selected_name: this.state.table,
                        disabled: this.props.is_dead,
                        greenButton: true,
                    }),
                ),
                React.createElement(
                    "div",
                    {
                        className:
                            "sm:ml-4 md:ml-0 my-4 md:my-0 md:absolute md:right-[10px]",
                    },
                    React.createElement("input", {
                        type: "text",
                        name: "search",
                        className: "form-control",
                        onChange: this.search.bind(this),
                        placeholder: "Search",
                        value: this.state.search_string,
                    }),
                ),
            ),
            this.renderTables(),
            this.state.show_action_confirmation_modal &&
                this.state.action_confirmation_type !== null &&
                modalPropsBuilder !== null
                ? React.createElement(BaseInventoryActionConfirmationModal, {
                      type: this.state.action_confirmation_type,
                      is_open: this.state.show_action_confirmation_modal,
                      manage_modal: this.manageConfirmationModal.bind(this),
                      title: modalPropsBuilder.fetchModalName(),
                      update_inventory: this.props.update_inventory,
                      set_success_message: this.setSuccessMessage.bind(this),
                      selected_item_names: this.state.selected_items.map(
                          function (selectedItem) {
                              return selectedItem.item_name;
                          },
                      ),
                      data: {
                          url: modalPropsBuilder.fetchActionUrl(
                              this.props.character_id,
                          ),
                          params: {
                              slot_ids: this.state.selected_items.map(
                                  function (selectedItem) {
                                      return selectedItem.slot_id;
                                  },
                              ),
                          },
                      },
                      usable_sets: this.props.usable_sets,
                  })
                : null,
            this.state.show_use_many && this.state.usable_items.length > 0
                ? React.createElement(InventoryUseManyItems, {
                      is_open: this.state.show_use_many,
                      manage_modal: this.manageUseManyItems.bind(this),
                      items: this.state.usable_items,
                      update_inventory: this.props.update_inventory,
                      character_id: this.props.character_id,
                      set_success_message: this.setSuccessMessage.bind(this),
                  })
                : null,
        );
    };
    return InventoryTabSection;
})(React.Component);
export default InventoryTabSection;
//# sourceMappingURL=inventory-tab-section.js.map
