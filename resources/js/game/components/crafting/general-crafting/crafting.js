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
import {
    craftingGetEndPoints,
    craftingPostEndPoints,
} from "./helpers/crafting-type-url";
import Ajax from "../../../lib/ajax/ajax";
import { formatNumber } from "../../../lib/game/format-number";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import { getCraftingType } from "./helpers/crafting-types";
import { isEqual } from "lodash";
import { generateServerMessage } from "../../../lib/ajax/generate-server-message";
import CraftingXp from "../base-components/skill-xp/crafting-xp";
import CraftingTypeSelection from "./crafting-partials/crafting-type-selecting";
import CraftingActionButtons from "./crafting-partials/crafting-action-buttons";
import ArmourTypeSelection from "./crafting-partials/armour-type-selection";
import SelectItemToCraft from "./crafting-partials/select-item-to-craft";
var Crafting = (function (_super) {
    __extends(Crafting, _super);
    function Crafting(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            selected_item: null,
            selected_type: null,
            armour_craft_type: null,
            loading: false,
            craftable_items: [],
            sorted_armour: [],
            show_craft_for_event: false,
            selected_armour_type: null,
            skill_xp: {
                curent_xp: 0,
                next_level_xp: 0,
                skill_name: "Unknown",
                level: 1,
            },
        };
        _this.characterStatus = Echo.private(
            "update-character-status-" + _this.props.user_id,
        );
        return _this;
    }
    Crafting.prototype.componentDidMount = function () {
        var _this = this;
        this.characterStatus.listen(
            "Game.Battle.Events.UpdateCharacterStatus",
            function (event) {
                _this.setState({
                    show_craft_for_event:
                        event.characterStatuses.show_craft_for_event,
                });
            },
        );
    };
    Crafting.prototype.showCraftForNpc = function () {
        var _this = this;
        if (!this.state.selected_type) {
            return false;
        }
        if (!this.state.selected_item) {
            return false;
        }
        if (!this.props.fame_tasks) {
            return false;
        }
        return (
            this.props.fame_tasks.filter(function (task) {
                return task.item_id === _this.state.selected_item.id;
            }).length > 0
        );
    };
    Crafting.prototype.setItemToCraft = function (data) {
        var foundItem = this.state.craftable_items.filter(function (item) {
            return item.id === parseInt(data.value);
        });
        if (foundItem.length > 0) {
            this.setState({
                selected_item: foundItem[0],
            });
        }
    };
    Crafting.prototype.setTypeToCraft = function (data) {
        var _this = this;
        this.setState(
            {
                selected_type: data.value,
                loading: true,
            },
            function () {
                if (
                    _this.state.selected_type !== null &&
                    _this.state.selected_type !== ""
                ) {
                    var url = craftingGetEndPoints(
                        "craft",
                        _this.props.character_id,
                    );
                    new Ajax()
                        .setRoute(url)
                        .setParameters({
                            crafting_type: _this.state.selected_type,
                        })
                        .doAjaxCall(
                            "get",
                            function (result) {
                                _this.setState({
                                    loading: false,
                                    craftable_items: result.data.items,
                                    skill_xp: result.data.xp,
                                    show_craft_for_event:
                                        result.data.show_craft_for_event,
                                });
                            },
                            function (error) {},
                        );
                }
            },
        );
    };
    Crafting.prototype.changeType = function () {
        if (
            this.state.sorted_armour.length > 0 &&
            this.state.armour_craft_type !== null
        ) {
            this.setState({
                sorted_armour: [],
                selected_item: null,
            });
        } else if (
            this.state.sorted_armour.length === 0 &&
            this.state.armour_craft_type !== null
        ) {
            this.setState({
                sorted_armour: [],
                armour_craft_type: null,
                selected_type: null,
                selected_item: null,
                craftable_items: [],
            });
        } else {
            this.setState({
                selected_type: null,
                selected_item: null,
                craftable_items: [],
            });
        }
    };
    Crafting.prototype.buildItems = function () {
        if (this.state.sorted_armour.length > 0) {
            return this.state.sorted_armour.map(function (item) {
                return {
                    label: item.name + " Gold Cost: " + formatNumber(item.cost),
                    value: item.id,
                };
            });
        }
        return this.state.craftable_items.map(function (item) {
            return {
                label: item.name + " Gold Cost: " + formatNumber(item.cost),
                value: item.id,
            };
        });
    };
    Crafting.prototype.defaultItem = function () {
        if (this.state.selected_item !== null) {
            return {
                label:
                    this.state.selected_item.name +
                    " Gold Cost: " +
                    formatNumber(this.state.selected_item.cost),
                value: this.state.selected_item.id,
            };
        }
        return { label: "Please select item to craft", value: 0 };
    };
    Crafting.prototype.craft = function (craftForNpc, craftForEvent) {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                var url = craftingPostEndPoints(
                    "craft",
                    _this.props.character_id,
                );
                new Ajax()
                    .setRoute(url)
                    .setParameters({
                        item_to_craft: _this.state.selected_item.id,
                        type: getCraftingType(_this.state.selected_item.type),
                        craft_for_npc: craftForNpc,
                        craft_for_event: craftForEvent,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            var oldCraftableItems = JSON.parse(
                                JSON.stringify(_this.state.craftable_items),
                            );
                            _this.setState(
                                {
                                    loading: false,
                                    craftable_items: result.data.items,
                                    skill_xp: result.data.xp,
                                    show_craft_for_event:
                                        result.data.show_craft_for_event,
                                },
                                function () {
                                    if (
                                        !isEqual(
                                            oldCraftableItems,
                                            result.data.items,
                                        )
                                    ) {
                                        _this.updateSortedArmour();
                                        generateServerMessage(
                                            "new_items",
                                            "You have new items to craft. Check the list!",
                                        );
                                    }
                                },
                            );
                        },
                        function (error) {},
                    );
            },
        );
    };
    Crafting.prototype.updateSortedArmour = function () {
        var _this = this;
        if (this.state.armour_craft_type != null) {
            var filteredArmour = this.state.craftable_items.filter(
                function (item) {
                    return item.type === _this.state.armour_craft_type;
                },
            );
            this.setState({
                sorted_armour: filteredArmour,
            });
        }
    };
    Crafting.prototype.clearCrafting = function () {
        this.props.remove_crafting();
    };
    Crafting.prototype.canCraft = function () {
        return (
            this.state.loading ||
            this.state.selected_item === null ||
            this.props.cannot_craft
        );
    };
    Crafting.prototype.canClose = function () {
        return this.state.loading;
    };
    Crafting.prototype.canChangeType = function () {
        return this.state.loading;
    };
    Crafting.prototype.selectedArmourType = function (data) {
        var filteredArmour = this.state.craftable_items.filter(function (item) {
            return item.type === data.value;
        });
        this.setState({
            sorted_armour: filteredArmour,
            armour_craft_type: data.value,
        });
    };
    Crafting.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                {
                    className:
                        "mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]",
                },
                React.createElement(
                    "div",
                    { className: "lg:cols-start-1 lg:col-span-2" },
                    this.state.selected_type === null
                        ? React.createElement(CraftingTypeSelection, {
                              select_type_to_craft:
                                  this.setTypeToCraft.bind(this),
                          })
                        : this.state.selected_type === "armour"
                          ? this.state.sorted_armour.length > 0
                              ? React.createElement(SelectItemToCraft, {
                                    set_item_to_craft:
                                        this.setItemToCraft.bind(this),
                                    items: this.buildItems(),
                                    default_item: this.defaultItem(),
                                })
                              : React.createElement(ArmourTypeSelection, {
                                    select_armour_type_to_craft:
                                        this.selectedArmourType.bind(this),
                                })
                          : React.createElement(SelectItemToCraft, {
                                set_item_to_craft:
                                    this.setItemToCraft.bind(this),
                                items: this.buildItems(),
                                default_item: this.defaultItem(),
                            }),
                    this.state.loading
                        ? React.createElement(LoadingProgressBar, null)
                        : null,
                    this.state.craftable_items.length > 0
                        ? React.createElement(CraftingXp, {
                              skill_xp: this.state.skill_xp,
                          })
                        : null,
                ),
            ),
            this.props.is_small
                ? React.createElement(
                      "div",
                      { className: "mt-3 mb-3 grid text-center" },
                      React.createElement(CraftingActionButtons, {
                          can_craft: this.canCraft(),
                          can_close: this.canClose(),
                          can_change_type: this.canChangeType(),
                          craft: this.craft.bind(this),
                          change_type: this.changeType.bind(this),
                          clear_crafting: this.clearCrafting.bind(this),
                          show_craft_for_npc: this.showCraftForNpc(),
                          show_craft_for_event: this.state.show_craft_for_event,
                      }),
                  )
                : React.createElement(
                      "div",
                      { className: "text-center lg:ml-[-100px] mt-3 mb-3" },
                      React.createElement(CraftingActionButtons, {
                          can_craft: this.canCraft(),
                          can_close: this.canClose(),
                          can_change_type: this.canChangeType(),
                          craft: this.craft.bind(this),
                          change_type: this.changeType.bind(this),
                          clear_crafting: this.clearCrafting.bind(this),
                          show_craft_for_npc: this.showCraftForNpc(),
                          show_craft_for_event: this.state.show_craft_for_event,
                      }),
                  ),
        );
    };
    return Crafting;
})(React.Component);
export default Crafting;
//# sourceMappingURL=crafting.js.map
