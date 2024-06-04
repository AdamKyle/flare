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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React, { Fragment } from "react";
import {
    craftingGetEndPoints,
    craftingPostEndPoints,
} from "../general-crafting/helpers/crafting-type-url";
import Ajax from "../../../lib/ajax/ajax";
import { formatNumber } from "../../../lib/game/format-number";
import Select from "react-select";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerButton from "../../ui/buttons/danger-button";
import { isEqual } from "lodash";
import { generateServerMessage } from "../../../lib/ajax/generate-server-message";
import CraftingXp from "../base-components/skill-xp/crafting-xp";
var Trinketry = (function (_super) {
    __extends(Trinketry, _super);
    function Trinketry(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            selected_item: null,
            craftable_items: [],
            skill_xp: {
                curent_xp: 0,
                next_level_xp: 0,
                skill_name: "Unknown",
                level: 1,
            },
        };
        return _this;
    }
    Trinketry.prototype.componentDidMount = function () {
        var _this = this;
        var url = craftingGetEndPoints("trinketry", this.props.character_id);
        new Ajax().setRoute(url).doAjaxCall(
            "get",
            function (result) {
                _this.setState({
                    loading: false,
                    craftable_items: result.data.items,
                    skill_xp: result.data.skill_xp,
                });
            },
            function (error) {},
        );
    };
    Trinketry.prototype.setItemToCraft = function (data) {
        var foundItem = this.state.craftable_items.filter(function (item) {
            return item.id === parseInt(data.value);
        });
        if (foundItem.length > 0) {
            this.setState({
                selected_item: foundItem[0],
            });
        }
    };
    Trinketry.prototype.buildItems = function () {
        return this.state.craftable_items.map(function (item) {
            return {
                label:
                    item.name +
                    ", Gold Dust Cost: " +
                    formatNumber(item.gold_dust_cost) +
                    " Copper Coin Cost: " +
                    formatNumber(item.copper_coin_cost),
                value: item.id,
            };
        });
    };
    Trinketry.prototype.defaultItem = function () {
        if (this.state.selected_item !== null) {
            var item = this.state.selected_item;
            return {
                label:
                    item.name +
                    ", Gold Dust Cost: " +
                    formatNumber(item.gold_dust_cost) +
                    " Copper Coins Cost: " +
                    formatNumber(item.copper_coin_cost),
                value: item.id,
            };
        }
        return { label: "Please select trinket to craft", value: 0 };
    };
    Trinketry.prototype.craft = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                var url = craftingPostEndPoints(
                    "trinketry",
                    _this.props.character_id,
                );
                new Ajax()
                    .setRoute(url)
                    .setParameters({
                        item_to_craft: _this.state.selected_item.id,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            var oldItems = JSON.parse(
                                JSON.stringify(_this.state.craftable_items),
                            );
                            _this.setState(
                                {
                                    loading: false,
                                    craftable_items: result.data.items,
                                    skill_xp: result.data.skill_xp,
                                },
                                function () {
                                    if (!isEqual(oldItems, result.data.items)) {
                                        generateServerMessage(
                                            "new_items",
                                            "You have new Trinkets to craft. Check the list!",
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
    Trinketry.prototype.clearCrafting = function () {
        this.props.remove_crafting();
    };
    Trinketry.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]" },
                React.createElement(
                    "div",
                    { className: "cols-start-1 col-span-2" },
                    React.createElement(Select, {
                        onChange: this.setItemToCraft.bind(this),
                        options: this.buildItems(),
                        menuPosition: "absolute",
                        menuPlacement: "bottom",
                        styles: {
                            menuPortal: function (base) {
                                return __assign(__assign({}, base), {
                                    zIndex: 9999,
                                    color: "#000000",
                                });
                            },
                        },
                        menuPortalTarget: document.body,
                        value: this.defaultItem(),
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
            React.createElement(
                "div",
                { className: "text-center md:ml-[-100px] mt-3 mb-3" },
                React.createElement(PrimaryButton, {
                    button_label: "Craft",
                    on_click: this.craft.bind(this),
                    disabled:
                        this.state.loading ||
                        this.state.selected_item === null ||
                        this.props.cannot_craft,
                }),
                React.createElement(DangerButton, {
                    button_label: "Close",
                    on_click: this.clearCrafting.bind(this),
                    additional_css: "ml-2",
                    disabled: this.state.loading || this.props.cannot_craft,
                }),
                React.createElement(
                    "a",
                    {
                        href: "/information/trinketry",
                        target: "_blank",
                        className: "ml-2",
                    },
                    "Help ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return Trinketry;
})(React.Component);
export default Trinketry;
//# sourceMappingURL=trinketry.js.map
