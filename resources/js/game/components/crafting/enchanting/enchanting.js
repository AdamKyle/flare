var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import React, { Fragment } from "react";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerButton from "../../ui/buttons/danger-button";
import { craftingGetEndPoints, craftingPostEndPoints, } from "../general-crafting/helpers/crafting-type-url";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Select from "react-select";
import { formatNumber } from "../../../lib/game/format-number";
import { isEqual } from "lodash";
import { generateServerMessage } from "../../../lib/ajax/generate-server-message";
import DangerLinkButton from "../../ui/buttons/danger-link-button";
import CraftingXp from "../base-components/skill-xp/crafting-xp";
import OrangeButton from "../../ui/buttons/orange-button";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import clsx from "clsx";
var Enchanting = (function (_super) {
    __extends(Enchanting, _super);
    function Enchanting(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            selected_item: null,
            selected_prefix: null,
            selected_suffix: null,
            selected_type: null,
            enchantable_items: [],
            event_items: [],
            show_enchanting_for_event: false,
            enchantments: [],
            info_message: null,
            skill_xp: {
                current_xp: 0,
                next_level_xp: 0,
                skill_name: "Unknown",
                level: 1,
            },
            hide_enchanting_help: false,
        };
        _this.characterStatus = Echo.private("update-character-status-" + _this.props.user_id);
        return _this;
    }
    Enchanting.prototype.componentDidMount = function () {
        var _this = this;
        if (localStorage.getItem("hide-enchanting-help") !== null) {
            this.setState({
                hide_enchanting_help: true,
            });
        }
        var url = craftingGetEndPoints("enchant", this.props.character_id);
        new Ajax().setRoute(url).doAjaxCall("get", function (result) {
            _this.setState({
                loading: false,
                enchantable_items: result.data.affixes.character_inventory,
                event_items: result.data.affixes.items_for_event,
                show_enchanting_for_event: result.data.affixes.show_enchanting_for_event,
                enchantments: result.data.affixes.affixes,
                skill_xp: result.data.skill_xp,
            });
        });
        this.characterStatus.listen("Game.Battle.Events.UpdateCharacterStatus", function (event) {
            _this.setState({
                show_enchanting_for_event: event.characterStatuses.show_enchanting_for_event,
            });
        });
    };
    Enchanting.prototype.clearCrafting = function () {
        this.props.remove_crafting();
    };
    Enchanting.prototype.enchant = function (enchantForEvent) {
        var _this = this;
        this.setState({
            loading: true,
            info_message: null,
        }, function () {
            var url = craftingPostEndPoints("enchant", _this.props.character_id);
            new Ajax()
                .setRoute(url)
                .setParameters({
                slot_id: _this.state.selected_item,
                affix_ids: [
                    _this.state.selected_prefix,
                    _this.state.selected_suffix,
                ],
                enchant_for_event: enchantForEvent,
            })
                .doAjaxCall("post", function (result) {
                var oldEnchantments = JSON.parse(JSON.stringify(_this.state.enchantments));
                _this.setState({
                    loading: false,
                    enchantable_items: result.data.affixes.character_inventory,
                    event_items: result.data.affixes.items_for_event,
                    show_enchanting_for_event: result.data.affixes
                        .show_enchanting_for_event,
                    enchantments: result.data.affixes.affixes,
                    skill_xp: result.data.skill_xp,
                }, function () {
                    if (!isEqual(oldEnchantments, result.data.affixes.affixes)) {
                        generateServerMessage("new_items", "You have new enchantments. Check the list(s)!");
                    }
                    if (result.data.affixes.items_for_event
                        .length > 0 &&
                        _this.state.selected_type === "event") {
                        _this.setState({
                            selected_item: result.data.affixes
                                .items_for_event[0].slot_id,
                        });
                    }
                    else if (result.data.affixes.character_inventory
                        .length > 0) {
                        _this.setState({
                            selected_item: result.data.affixes
                                .character_inventory[0]
                                .slot_id,
                        });
                    }
                    if (result.data.affixes.items_for_event
                        .length <= 0) {
                        _this.setState({
                            selected_type: "regular",
                        });
                    }
                });
            }, function (error) { });
        });
    };
    Enchanting.prototype.setSelectedItem = function (data) {
        this.setState({
            selected_item: parseInt(data.value),
        });
    };
    Enchanting.prototype.setTypeOfEnchanting = function (data) {
        this.setState({
            selected_type: data.value,
        });
    };
    Enchanting.prototype.setPrefix = function (data) {
        this.setState({
            selected_prefix: parseInt(data.value),
        });
    };
    Enchanting.prototype.setSuffix = function (data) {
        this.setState({
            selected_suffix: parseInt(data.value),
        });
    };
    Enchanting.prototype.renderItemsToEnchantSelection = function () {
        if (this.state.selected_type === null) {
            return this.state.enchantable_items.map(function (item) {
                return {
                    label: item.item_name,
                    value: item.slot_id,
                };
            });
        }
        if (this.state.selected_type !== "regular") {
            return this.state.event_items.map(function (item) {
                return {
                    label: item.item_name,
                    value: item.slot_id,
                };
            });
        }
        return this.state.enchantable_items.map(function (item) {
            return {
                label: item.item_name,
                value: item.slot_id,
            };
        });
    };
    Enchanting.prototype.renderEnchantmentTypes = function () {
        return [
            {
                label: "Event",
                value: "event",
            },
            {
                label: "Regular",
                value: "regular",
            },
        ];
    };
    Enchanting.prototype.resetPrefixes = function () {
        this.setState({
            selected_prefix: null,
        });
    };
    Enchanting.prototype.resetSuffixes = function () {
        this.setState({
            selected_suffix: null,
        });
    };
    Enchanting.prototype.renderEnchantmentOptions = function (type) {
        var enchantments = this.state.enchantments
            .filter(function (enchantment) {
            return enchantment.type === type;
        })
            .sort(function (a, b) { return a.cost - b.cost; });
        return enchantments.map(function (enchantment) {
            return {
                label: enchantment.name +
                    " [Cost: " +
                    formatNumber(enchantment.cost) +
                    ", INT REQ: " +
                    formatNumber(enchantment.int_required) +
                    "]",
                value: enchantment.id,
            };
        });
    };
    Enchanting.prototype.selectedItemToEnchant = function () {
        var _this = this;
        if (this.state.selected_item !== null) {
            var foundItem = this.state.enchantable_items.filter(function (item) {
                return item.slot_id === _this.state.selected_item;
            });
            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item_name,
                    value: this.state.selected_item,
                };
            }
            foundItem = this.state.event_items.filter(function (item) {
                return item.slot_id === _this.state.selected_item;
            });
            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item_name,
                    value: this.state.selected_item,
                };
            }
        }
        return {
            label: "Please select item.",
            value: 0,
        };
    };
    Enchanting.prototype.selectedEnchantment = function (type) {
        var selectedType = this.state["selected_" + type];
        if (selectedType !== null) {
            var foundEnchantment = this.state.enchantments.filter(function (item) {
                return item.id === selectedType;
            });
            if (foundEnchantment.length > 0) {
                return {
                    label: foundEnchantment[0].name +
                        " Cost: " +
                        formatNumber(foundEnchantment[0].cost),
                    value: selectedType,
                };
            }
        }
        return {
            label: "Please select " + type + " enchantment.",
            value: 0,
        };
    };
    Enchanting.prototype.selectedTypeOfEnchantment = function () {
        if (this.state.selected_type !== null) {
            return {
                label: this.state.selected_type === "regular"
                    ? "Regular"
                    : "Event",
                value: this.state.selected_type,
            };
        }
        return {
            label: "Please select which type of enchanting.",
            value: null,
        };
    };
    Enchanting.prototype.cannotCraft = function () {
        return (this.state.loading ||
            this.state.selected_item === null ||
            this.props.cannot_craft ||
            (this.state.selected_prefix === null &&
                this.state.selected_suffix === null));
    };
    Enchanting.prototype.hideEnchantingHelp = function () {
        localStorage.setItem("hide-enchanting-help", "true");
        this.setState({
            hide_enchanting_help: true,
        });
    };
    Enchanting.prototype.render = function () {
        var _this = this;
        return (React.createElement(Fragment, null,
            React.createElement("div", { className: "mt-2 grid lg:grid-cols-3 gap-2 lg:ml-[120px]" },
                this.state.show_enchanting_for_event &&
                    this.state.event_items.length > 0 ? (React.createElement("div", { className: "col-start-1 col-span-2" },
                    React.createElement(Select, { onChange: this.setTypeOfEnchanting.bind(this), options: this.renderEnchantmentTypes(), menuPosition: "absolute", menuPlacement: "bottom", styles: {
                            menuPortal: function (base) { return (__assign(__assign({}, base), { zIndex: 9999, color: "#000000" })); },
                        }, menuPortalTarget: document.body, value: this.selectedTypeOfEnchantment() }))) : null,
                React.createElement("div", { className: "col-start-1 col-span-2" },
                    React.createElement(Select, { onChange: this.setSelectedItem.bind(this), options: this.renderItemsToEnchantSelection(), menuPosition: "absolute", menuPlacement: "bottom", styles: {
                            menuPortal: function (base) { return (__assign(__assign({}, base), { zIndex: 9999, color: "#000000" })); },
                        }, menuPortalTarget: document.body, value: this.selectedItemToEnchant(), isDisabled: this.state.show_enchanting_for_event &&
                            this.state.event_items.length > 0 &&
                            this.state.selected_type === null })),
                React.createElement("div", { className: "col-start-1 col-span-2" },
                    React.createElement("div", { className: "lg:hidden grid grid-cols-3" },
                        React.createElement("div", { className: "col-start-1 col-span-2" },
                            React.createElement(Select, { onChange: this.setPrefix.bind(this), options: this.renderEnchantmentOptions("prefix"), menuPosition: "absolute", menuPlacement: "bottom", styles: {
                                    menuPortal: function (base) { return (__assign(__assign({}, base), { zIndex: 9999, color: "#000000" })); },
                                }, menuPortalTarget: document.body, value: this.selectedEnchantment("prefix"), isDisabled: (this.state.show_enchanting_for_event &&
                                    this.state.event_items.length > 0 &&
                                    this.state.selected_type ===
                                        null) ||
                                    this.state.selected_item === null })),
                        React.createElement("div", { className: "cols-start-3 cols-end-3 mt-2 ml-4" },
                            React.createElement(DangerLinkButton, { button_label: "Clear", on_click: this.resetPrefixes.bind(this) }))),
                    React.createElement("div", { className: "hidden lg:block" },
                        React.createElement(Select, { onChange: this.setPrefix.bind(this), options: this.renderEnchantmentOptions("prefix"), menuPosition: "absolute", menuPlacement: "bottom", styles: {
                                menuPortal: function (base) { return (__assign(__assign({}, base), { zIndex: 9999, color: "#000000" })); },
                            }, menuPortalTarget: document.body, value: this.selectedEnchantment("prefix"), isDisabled: (this.state.show_enchanting_for_event &&
                                this.state.event_items.length > 0 &&
                                this.state.selected_type === null) ||
                                this.state.selected_item === null }))),
                React.createElement("div", { className: "hidden lg:block cols-start-3 cols-end-3 mt-2" },
                    React.createElement(DangerLinkButton, { button_label: "Clear", on_click: this.resetPrefixes.bind(this) })),
                React.createElement("div", { className: "col-start-1 col-span-2" },
                    React.createElement("div", { className: "lg:hidden grid grid-cols-3" },
                        React.createElement("div", { className: "col-start-1 col-span-2" },
                            React.createElement(Select, { onChange: this.setSuffix.bind(this), options: this.renderEnchantmentOptions("suffix"), menuPosition: "absolute", menuPlacement: "bottom", styles: {
                                    menuPortal: function (base) { return (__assign(__assign({}, base), { zIndex: 9999, color: "#000000" })); },
                                }, menuPortalTarget: document.body, value: this.selectedEnchantment("suffix"), isDisabled: (this.state.show_enchanting_for_event &&
                                    this.state.event_items.length > 0 &&
                                    this.state.selected_type ===
                                        null) ||
                                    this.state.selected_item === null })),
                        React.createElement("div", { className: "cols-start-3 cols-end-3 mt-2 ml-4" },
                            React.createElement(DangerLinkButton, { button_label: "Clear", on_click: this.resetSuffixes.bind(this) }))),
                    React.createElement("div", { className: "hidden lg:block" },
                        React.createElement(Select, { onChange: this.setSuffix.bind(this), options: this.renderEnchantmentOptions("suffix"), menuPosition: "absolute", menuPlacement: "bottom", styles: {
                                menuPortal: function (base) { return (__assign(__assign({}, base), { zIndex: 9999, color: "#000000" })); },
                            }, menuPortalTarget: document.body, value: this.selectedEnchantment("suffix"), isDisabled: (this.state.show_enchanting_for_event &&
                                this.state.event_items.length > 0 &&
                                this.state.selected_type === null) ||
                                this.state.selected_item === null }))),
                React.createElement("div", { className: "hidden lg:block cols-start-3 cols-end-3 mt-2" },
                    React.createElement(DangerLinkButton, { button_label: "Clear", on_click: this.resetSuffixes.bind(this) }))),
            React.createElement("div", { className: "m-auto lg:w-1/2 relative lg:left-[-60px]" },
                React.createElement(InfoAlert, { additional_css: clsx("my-4", {
                        hidden: this.state.hide_enchanting_help,
                    }), close_alert: this.hideEnchantingHelp.bind(this) },
                    React.createElement("p", { className: "my-2" },
                        React.createElement("strong", { className: "my-2" }, "Pay attention to your Server Message chat tab.")),
                    React.createElement("p", { className: "mb-2" },
                        "Enchanting requires you to raise your character INT and your Enchanting skill. Players will run into an issue where they unlock new enchants but cannot craft them because their INT is too low. You can raise this, regardless of class, by equipping staves, damage spells or utilizing the",
                        " ",
                        React.createElement("a", { href: "/information/class-ranks", target: "_blank", className: "ml-2" },
                            "Class Ranks",
                            " ",
                            React.createElement("i", { className: "fas fa-external-link-alt" })),
                        " ",
                        "or equipping Stat Modifier enchants or Spell Crafting enchants will also raise your INT."),
                    React.createElement("p", { className: "mb-2" }, "Sometimes, you just need to level your character as well. Never underestimate a bit of grind."),
                    React.createElement("p", null, "Click Help below for more info."))),
            React.createElement("div", { className: "m-auto lg:w-1/2 relative lg:left-[-60px]" },
                this.state.loading ? React.createElement(LoadingProgressBar, null) : null,
                this.state.enchantments.length > 0 ? (React.createElement("div", { className: "ml-[25px] lg:ml-0 mb-2 lg:mb-0" },
                    React.createElement(CraftingXp, { skill_xp: this.state.skill_xp }))) : null),
            this.state.event_items.length <= 0 &&
                this.state.show_enchanting_for_event ? (React.createElement(InfoAlert, { additional_css: "my-4 m-auto lg:w-1/2 relative lg:left-[-60px]" }, "You have no event crafted items. You can craft your own items and either enchant them for your self or enchant for event and participate in the event for a Legendary item.")) : null,
            React.createElement("div", { className: "text-center md:ml-[-100px] mt-3 mb-3" },
                React.createElement(PrimaryButton, { button_label: "Enchant", on_click: function () { return _this.enchant(false); }, disabled: this.cannotCraft() }),
                this.state.show_enchanting_for_event ? (React.createElement(OrangeButton, { button_label: "Enchant for event", on_click: function () { return _this.enchant(true); }, disabled: this.cannotCraft(), additional_css: "ml-2" })) : null,
                React.createElement(DangerButton, { button_label: "Close", on_click: this.clearCrafting.bind(this), additional_css: "ml-2", disabled: this.state.loading || this.props.cannot_craft }),
                React.createElement("a", { href: "/information/enchanting", target: "_blank", className: "ml-2" },
                    "Help ",
                    React.createElement("i", { className: "fas fa-external-link-alt" })))));
    };
    return Enchanting;
}(React.Component));
export default Enchanting;
//# sourceMappingURL=enchanting.js.map