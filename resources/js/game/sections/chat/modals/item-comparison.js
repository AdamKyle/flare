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
import ItemNameColorationText from "../../../components/items/item-name/item-name-coloration-text";
import { capitalize } from "lodash";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Ajax from "../../../lib/ajax/ajax";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import { watchForChatDarkModeComparisonChange } from "../../../lib/game/dark-mode-watcher";
import InventoryQuestItemDetails from "../../character-sheet/components/modals/components/inventory-quest-item-details";
import AlchemyItemHoly from "../../../components/modals/item-details/item-views/alchemy-item-holy";
import GemDetails from "../../../components/modals/item-details/item-views/gem-details";
var ItemComparison = (function (_super) {
    __extends(ItemComparison, _super);
    function ItemComparison(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            comparison_details: null,
            usable_sets: [],
            action_loading: false,
            loading: true,
            dark_charts: false,
            error_message: null,
        };
        return _this;
    }
    ItemComparison.prototype.componentDidMount = function () {
        var _this = this;
        watchForChatDarkModeComparisonChange(this);
        new Ajax()
            .setRoute(
                "character/" +
                    this.props.character_id +
                    "/inventory/comparison-from-chat",
            )
            .setParameters({
                id: this.props.slot_id,
            })
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        comparison_details: result.data.comparison_data,
                        usable_sets: result.data.usable_sets,
                    });
                },
                function (error) {
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        if (response.status === 404) {
                            _this.setState({
                                error_message:
                                    "Item no longer exists in your inventory...",
                                loading: false,
                            });
                        }
                    }
                },
            );
    };
    ItemComparison.prototype.getTheName = function () {
        var item = this.state.comparison_details.itemToEquip;
        if (typeof item.affix_name === "undefined") {
            return item.name;
        }
        return item.affix_name;
    };
    ItemComparison.prototype.buildTitle = function () {
        if (this.state.error_message !== null) {
            return "Um ... ERROR!";
        }
        if (this.state.comparison_details === null) {
            return "Loading comparison data ...";
        }
        return React.createElement(
            "div",
            { className: "grid grid-cols-2 gap-2" },
            this.state.comparison_details.itemToEquip.type === "gem"
                ? React.createElement(
                      "span",
                      { className: "text-lime-600 dark:text-lime-500" },
                      this.state.comparison_details.itemToEquip.item.gem.name,
                  )
                : React.createElement(ItemNameColorationText, {
                      custom_width: false,
                      item: {
                          name: this.getTheName(),
                          type: this.state.comparison_details.itemToEquip.type,
                          affix_count:
                              this.state.comparison_details.itemToEquip
                                  .affix_count,
                          is_unique:
                              this.state.comparison_details.itemToEquip
                                  .is_unique,
                          is_mythic:
                              this.state.comparison_details.itemToEquip
                                  .is_mythic,
                          is_cosmic:
                              this.state.comparison_details.itemtoEquip
                                  .is_cosmic,
                          holy_stacks_applied:
                              this.state.comparison_details.itemToEquip
                                  .holy_stacks_applied,
                      },
                  }),
            React.createElement(
                "div",
                { className: "absolute right-[-30px] md:right-0" },
                React.createElement(
                    "span",
                    { className: "pl-3 text-right mr-[70px]" },
                    "(Type:",
                    " ",
                    capitalize(this.state.comparison_details.itemToEquip.type)
                        .split("-")
                        .join(" "),
                    ")",
                ),
            ),
        );
    };
    ItemComparison.prototype.isGridSize = function (size, itemToEquip) {
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
    ItemComparison.prototype.renderViewForType = function (type, holy_number) {
        if (type === "alchemy") {
            if (typeof holy_number !== "undefined" && holy_number !== null) {
                return React.createElement(AlchemyItemHoly, {
                    item: this.state.comparison_details.itemToEquip,
                });
            }
        }
        if (type === "quest") {
            return React.createElement(InventoryQuestItemDetails, {
                item: this.state.comparison_details.itemToEquip,
            });
        }
        if (type === "gem") {
            return React.createElement(GemDetails, {
                gem: this.state.comparison_details.itemToEquip.item.gem,
            });
        }
    };
    ItemComparison.prototype.render = function () {
        if (this.props.is_dead) {
            return React.createElement(
                Dialogue,
                {
                    is_open: this.props.is_open,
                    handle_close: this.props.manage_modal,
                    title: "You are dead",
                    large_modal: true,
                    primary_button_disabled: false,
                },
                React.createElement(
                    "p",
                    { className: "text-red-700 dark:text-red-400" },
                    "And you thought dead people could manage their inventory. Go to the game tab, click revive and live again.",
                ),
            );
        }
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.buildTitle(),
                large_modal: true,
                primary_button_disabled: this.state.action_loading,
            },
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "p-5 mb-2" },
                      React.createElement(ComponentLoading, null),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      this.state.error_message !== null
                          ? React.createElement(
                                "div",
                                {
                                    className:
                                        "mx-4 text-red-500 dark:text-red-400 text-center text-lg",
                                },
                                this.state.error_message,
                            )
                          : this.renderViewForType(
                                this.state.comparison_details.itemToEquip.type,
                                this.state.comparison_details.itemToEquip
                                    .holy_level,
                            ),
                  ),
        );
    };
    return ItemComparison;
})(React.Component);
export default ItemComparison;
//# sourceMappingURL=item-comparison.js.map
