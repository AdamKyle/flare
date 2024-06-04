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
import React from "react";
import clsx from "clsx";
import PrimaryOutlineButton from "../../ui/buttons/primary-outline-button";
import SuccessOutlineButton from "../../ui/buttons/success-outline-button";
import DangerOutlineButton from "../../ui/buttons/danger-outline-button";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import EquipModal from "./action-modals/equip-modal";
import MoveItemModal from "./action-modals/move-item-modal";
import SellItemModal from "./action-modals/sell-item-modal";
import ListItemModal from "./action-modals/list-item-modal";
import InventoryUseDetails from "../../../sections/character-sheet/components/modals/inventory-item-details";
import InventoryComparisonActionsAjax from "./ajax/inventory-comparison-actions-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
var ItemActions = (function (_super) {
    __extends(ItemActions, _super);
    function ItemActions(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_equip_modal: false,
            show_move_modal: false,
            show_sell_modal: false,
            show_list_item_modal: false,
            item_to_sell: null,
            item_to_show: null,
            show_item_details: false,
            show_loading_label: false,
            loading_label: null,
            error_message: null,
            success_message: null,
            has_updated_item: false,
        };
        _this.inventoryComparisonAjax = serviceContainer().fetch(
            InventoryComparisonActionsAjax,
        );
        return _this;
    }
    ItemActions.prototype.isGridSize = function (size, itemToEquip) {
        switch (size) {
            case 5:
                return (
                    itemToEquip.affix_count === 0 &&
                    itemToEquip.holy_stacks_applied === 0 &&
                    !itemToEquip.is_unique
                );
            case 7:
                return (
                    itemToEquip.affix_count > 0 ||
                    itemToEquip.holy_stacks_applied > 0 ||
                    itemToEquip.is_unique
                );
            default:
                return false;
        }
    };
    ItemActions.prototype.manageEquipModal = function () {
        this.setState({
            show_equip_modal: !this.state.show_equip_modal,
        });
    };
    ItemActions.prototype.manageMoveModalModal = function () {
        this.setState({
            show_move_modal: !this.state.show_move_modal,
        });
    };
    ItemActions.prototype.manageSellModal = function (item) {
        if (!item) {
            this.setState({
                show_sell_modal: !this.state.show_sell_modal,
                item_to_sell: null,
            });
            return;
        }
        this.setState({
            show_sell_modal: !this.state.show_sell_modal,
            item_to_sell: item,
        });
    };
    ItemActions.prototype.manageViewItemDetails = function (item) {
        this.setState({
            show_item_details: !this.state.show_item_details,
            item_to_show: item,
        });
    };
    ItemActions.prototype.manageListItemModal = function (item) {
        this.setState({
            show_list_item_modal: !this.state.show_list_item_modal,
            item_to_sell: item,
        });
    };
    ItemActions.prototype.equipItem = function (type, position) {
        var _this = this;
        this.setState(
            {
                show_loading_label: true,
                loading_label:
                    "Equipping set and recalculating your stats (this can take a few seconds) ...",
            },
            function () {
                var params = {
                    position: position,
                    slot_id: _this.props.slot_id,
                    equip_type: type,
                };
                _this.inventoryComparisonAjax.equipItem(_this, params);
            },
        );
    };
    ItemActions.prototype.moveItem = function (setId) {
        this.setState({
            show_loading_label: true,
            loading_label: "Moving item to selected set ...",
        });
        var params = {
            move_to_set: setId,
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };
        this.inventoryComparisonAjax.moveItem(this, params);
    };
    ItemActions.prototype.sellItem = function () {
        this.setState({
            show_loading_label: true,
            loading_label: "selling selected item ...",
        });
        var params = {
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };
        this.inventoryComparisonAjax.sellItem(this, params);
    };
    ItemActions.prototype.listItem = function (price) {
        this.setState({
            show_loading_label: true,
            loading_label: "Listing selected item ...",
        });
        var params = {
            list_for: price,
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };
        this.inventoryComparisonAjax.listItem(this, params);
    };
    ItemActions.prototype.disenchantItem = function () {
        this.setState({
            show_loading_label: true,
            loading_label: "Disenchanting selected item ...",
        });
        this.inventoryComparisonAjax.disenchantItem(this);
    };
    ItemActions.prototype.destroyItem = function () {
        this.setState({
            show_loading_label: true,
            loading_label: "Destroying item ...",
        });
        var params = {
            slot_id: this.props.comparison_details.itemToEquip.slot_id,
        };
        this.inventoryComparisonAjax.destroyItem(this, params);
    };
    ItemActions.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "mt-6 mb-4 md:m-auto md:w-3/4 w-full" },
                this.state.show_loading_label
                    ? React.createElement(LoadingProgressBar, {
                          show_label: this.state.show_loading_label,
                          label: this.state.loading_label,
                      })
                    : null,
                this.state.error_message !== null
                    ? React.createElement(
                          DangerAlert,
                          { additional_css: "my-4" },
                          this.state.error_message,
                      )
                    : null,
                this.state.success_message !== null
                    ? React.createElement(
                          SuccessAlert,
                          { additional_css: "my-4" },
                          this.state.success_message,
                      )
                    : null,
            ),
            React.createElement(
                "div",
                {
                    className: clsx(
                        "grid grid-cols-1 w-full gap-2 md:m-auto md:w-3/4 max-h-[150px] md:max-h-auto overflow-y-auto",
                        {
                            "md:grid-cols-7": this.isGridSize(
                                7,
                                this.props.comparison_details.itemToEquip,
                            ),
                            "md:grid-cols-5": this.isGridSize(
                                5,
                                this.props.comparison_details.itemToEquip,
                            ),
                            hidden:
                                this.props.comparison_details.itemToEquip
                                    .type === "quest",
                        },
                    ),
                },
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Details",
                    on_click: function () {
                        return _this.manageViewItemDetails(
                            _this.props.comparison_details.itemToEquip,
                        );
                    },
                    disabled:
                        this.state.show_loading_label ||
                        this.state.has_updated_item,
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Equip",
                    on_click: this.manageEquipModal.bind(this),
                    disabled:
                        this.state.show_loading_label ||
                        this.props.is_automation_running ||
                        this.props.is_dead ||
                        this.state.has_updated_item,
                }),
                React.createElement(PrimaryOutlineButton, {
                    button_label: "Move",
                    on_click: this.manageMoveModalModal.bind(this),
                    disabled:
                        this.state.show_loading_label ||
                        this.state.has_updated_item ||
                        this.props.is_dead,
                }),
                this.props.comparison_details.itemToEquip.type !== "trinket" &&
                    this.props.comparison_details.itemToEquip.type !==
                        "artifact"
                    ? React.createElement(SuccessOutlineButton, {
                          button_label: "Sell",
                          on_click: function () {
                              return _this.manageSellModal(
                                  _this.props.comparison_details.itemToEquip,
                              );
                          },
                          disabled:
                              this.state.show_loading_label ||
                              this.state.has_updated_item ||
                              this.props.is_dead,
                      })
                    : null,
                this.props.comparison_details.itemToEquip.affix_count > 0 ||
                    this.props.comparison_details.itemToEquip
                        .holy_stacks_applied > 0 ||
                    this.props.comparison_details.itemToEquip.type === "trinket"
                    ? React.createElement(SuccessOutlineButton, {
                          button_label: "List",
                          on_click: function () {
                              return _this.manageListItemModal(
                                  _this.props.comparison_details.itemToEquip,
                              );
                          },
                          disabled:
                              this.state.show_loading_label ||
                              this.props.is_automation_running ||
                              this.props.is_dead ||
                              this.state.has_updated_item,
                      })
                    : null,
                this.props.comparison_details.itemToEquip.affix_count > 0
                    ? React.createElement(DangerOutlineButton, {
                          button_label: "Disenchant",
                          on_click: this.disenchantItem.bind(this),
                          disabled:
                              this.state.show_loading_label ||
                              this.state.has_updated_item ||
                              this.props.is_dead,
                      })
                    : null,
                React.createElement(DangerOutlineButton, {
                    button_label: "Destroy",
                    on_click: this.destroyItem.bind(this),
                    disabled:
                        this.state.show_loading_label ||
                        this.state.has_updated_item ||
                        this.props.is_dead,
                }),
            ),
            this.state.show_equip_modal
                ? React.createElement(EquipModal, {
                      is_open: this.state.show_equip_modal,
                      manage_modal: this.manageEquipModal.bind(this),
                      item_to_equip: this.props.comparison_details.itemToEquip,
                      equip_item: this.equipItem.bind(this),
                      is_bow_equipped:
                          this.props.comparison_details.bowEquipped,
                      is_hammer_equipped:
                          this.props.comparison_details.hammerEquipped,
                      is_stave_equipped:
                          this.props.comparison_details.staveEquipped,
                  })
                : null,
            this.state.show_move_modal
                ? React.createElement(MoveItemModal, {
                      is_open: this.state.show_move_modal,
                      manage_modal: this.manageMoveModalModal.bind(this),
                      usable_sets: this.props.usable_sets,
                      move_item: this.moveItem.bind(this),
                  })
                : null,
            this.state.show_sell_modal && this.state.item_to_sell !== null
                ? React.createElement(SellItemModal, {
                      is_open: this.state.show_sell_modal,
                      manage_modal: this.manageSellModal.bind(this),
                      sell_item: this.sellItem.bind(this),
                      item: this.state.item_to_sell,
                  })
                : null,
            this.state.show_list_item_modal
                ? React.createElement(ListItemModal, {
                      is_open: this.state.show_list_item_modal,
                      manage_modal: this.manageListItemModal.bind(this),
                      list_item: this.listItem.bind(this),
                      item: this.state.item_to_sell,
                      dark_charts: this.props.dark_charts,
                  })
                : null,
            this.state.show_item_details && this.state.item_to_show !== null
                ? React.createElement(InventoryUseDetails, {
                      character_id: this.props.character_id,
                      item_id: this.state.item_to_show.id,
                      is_open: this.state.show_item_details,
                      manage_modal: this.manageViewItemDetails.bind(this),
                  })
                : null,
        );
    };
    return ItemActions;
})(React.Component);
export default ItemActions;
//# sourceMappingURL=item-actions.js.map
