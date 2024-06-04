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
} from "../../crafting/general-crafting/helpers/crafting-type-url";
import Ajax from "../../../lib/ajax/ajax";
import Select from "react-select";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerButton from "../../ui/buttons/danger-button";
import { formatNumber } from "../../../lib/game/format-number";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
var WorkBench = (function (_super) {
    __extends(WorkBench, _super);
    function WorkBench(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            applying_oil: false,
            selected_item: null,
            selected_alchemy_item: null,
            selected_item_name: null,
            inventory_items: [],
            alchemy_items: [],
            max_holy_stacks: 0,
            applied_holy_stacks: 0,
            error_message: null,
            cost: 0,
        };
        return _this;
    }
    WorkBench.prototype.componentDidMount = function () {
        var _this = this;
        var url = craftingGetEndPoints("workbench", this.props.character_id);
        new Ajax().setRoute(url).doAjaxCall(
            "get",
            function (result) {
                _this.setState({
                    loading: false,
                    inventory_items: result.data.items,
                    alchemy_items: result.data.alchemy_items,
                });
            },
            function (error) {
                _this.setState({ loading: false });
            },
        );
    };
    WorkBench.prototype.applyHolyOil = function () {
        var _this = this;
        var url = craftingPostEndPoints("workbench", this.props.character_id);
        this.setState(
            {
                applying_oil: true,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(url)
                    .setParameters({
                        item_id: _this.state.selected_item,
                        alchemy_item_id: _this.state.selected_alchemy_item,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    applying_oil: false,
                                    inventory_items: result.data.items,
                                    alchemy_items: result.data.alchemy_items,
                                },
                                function () {
                                    var foundItem =
                                        _this.state.inventory_items.filter(
                                            function (slot) {
                                                return (
                                                    slot.item.affix_name ===
                                                    _this.state
                                                        .selected_item_name
                                                );
                                            },
                                        );
                                    if (foundItem.length > 0) {
                                        _this.setState({
                                            selected_item: foundItem[0].item.id,
                                            max_holy_stacks:
                                                foundItem[0].item.holy_stacks,
                                            applied_holy_stacks:
                                                foundItem[0].item
                                                    .holy_stacks_applied,
                                        });
                                    } else {
                                        _this.setState({
                                            applied_holy_stacks: 0,
                                            max_holy_stacks: 0,
                                            selected_item: null,
                                        });
                                    }
                                },
                            );
                        },
                        function (error) {
                            _this.setState({ applying_oil: false });
                            if (error.response) {
                                _this.setState({
                                    error_message: error.response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    WorkBench.prototype.setItem = function (data) {
        var _this = this;
        this.setState(
            {
                selected_item: parseInt(data.value),
            },
            function () {
                var foundItem = _this.state.inventory_items.filter(
                    function (slot) {
                        return slot.item.id === parseInt(data.value);
                    },
                );
                if (foundItem.length > 0) {
                    _this.setState({
                        selected_item_name: foundItem[0].item.affix_name,
                        max_holy_stacks: foundItem[0].item.holy_stacks,
                        applied_holy_stacks:
                            foundItem[0].item.holy_stacks_applied,
                    });
                }
            },
        );
    };
    WorkBench.prototype.setAlchemyItem = function (data) {
        var _this = this;
        var foundAlchemyItem = this.state.alchemy_items.filter(function (slot) {
            return slot.item.id === data.value;
        });
        var foundSelectedItem = this.state.inventory_items.filter(
            function (slot) {
                return slot.item.id === _this.state.selected_item;
            },
        );
        if (foundAlchemyItem.length === 0 && foundSelectedItem.length === 0) {
            return;
        }
        var alchemyItem = foundAlchemyItem[0].item;
        var selectedItem = foundSelectedItem[0].item;
        var baseCost = selectedItem.holy_stacks * 100;
        var cost = baseCost * alchemyItem.holy_level;
        this.setState({
            selected_alchemy_item: parseInt(data.value),
            cost: cost,
        });
    };
    WorkBench.prototype.buildItems = function () {
        return this.state.inventory_items
            .filter(function (slot) {
                return slot.item.type !== "alchemy";
            })
            .map(function (slot) {
                return {
                    label: slot.item.affix_name,
                    value: slot.item.id,
                };
            });
    };
    WorkBench.prototype.selectedItem = function () {
        var _this = this;
        if (this.state.selected_item !== null) {
            var foundItem = this.state.inventory_items.filter(function (slot) {
                return slot.item.id === _this.state.selected_item;
            });
            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item.affix_name,
                    value: this.state.selected_item,
                };
            }
        }
        return { label: "Please Select an Item", value: 0 };
    };
    WorkBench.prototype.buildAlchemicalItems = function () {
        return this.state.alchemy_items.map(function (slot) {
            return {
                label: slot.item.name,
                value: slot.item.id,
            };
        });
    };
    WorkBench.prototype.selectedAlchemyItem = function () {
        var _this = this;
        if (this.state.selected_alchemy_item !== null) {
            var foundItem = this.state.alchemy_items.filter(function (slot) {
                return slot.item.id === _this.state.selected_alchemy_item;
            });
            if (foundItem.length > 0) {
                return {
                    label: foundItem[0].item.name,
                    value: this.state.selected_alchemy_item,
                };
            }
        }
        return { label: "Please Selected an Alchemical Item", value: 0 };
    };
    WorkBench.prototype.clearCrafting = function () {
        this.props.remove_crafting();
    };
    WorkBench.prototype.isApplyButtonDisabled = function () {
        return (
            this.state.loading ||
            this.state.selected_item === null ||
            this.state.selected_alchemical_item === null ||
            this.props.cannot_craft
        );
    };
    WorkBench.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(
                "div",
                { className: "mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]" },
                React.createElement(
                    "div",
                    { className: "col-start-1 col-span-2" },
                    React.createElement(LoadingProgressBar, null),
                ),
            );
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]" },
                React.createElement(
                    "div",
                    { className: "col-start-1 col-span-2" },
                    this.state.inventory_items.length > 0
                        ? React.createElement(Select, {
                              onChange: this.setItem.bind(this),
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
                              value: this.selectedItem(),
                          })
                        : React.createElement(
                              "p",
                              {
                                  className:
                                      "mt-2 text-red-400 text-red-500 text-center",
                              },
                              "No valid items in your inventory to apply oils to.",
                          ),
                ),
                React.createElement(
                    "div",
                    { className: "col-start-1 col-span-2" },
                    this.state.selected_item !== null &&
                        this.state.alchemy_items.length > 0
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement(Select, {
                                  onChange: this.setAlchemyItem.bind(this),
                                  options: this.buildAlchemicalItems(),
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
                                  value: this.selectedAlchemyItem(),
                              }),
                              this.state.selected_alchemy_item !== null
                                  ? React.createElement(
                                        "div",
                                        { className: "my-2" },
                                        React.createElement(
                                            "dl",
                                            null,
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Gold Dust Cost:",
                                            ),
                                            React.createElement(
                                                "dd",
                                                null,
                                                formatNumber(this.state.cost),
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Applied Holy Stacks",
                                            ),
                                            React.createElement(
                                                "dd",
                                                null,
                                                this.state.applied_holy_stacks,
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Max Holy Stacks",
                                            ),
                                            React.createElement(
                                                "dd",
                                                null,
                                                this.state.max_holy_stacks,
                                            ),
                                        ),
                                    )
                                  : null,
                          )
                        : this.state.selected_item !== null &&
                            this.state.alchemy_items.length === 0 &&
                            this.state.error_message === null
                          ? React.createElement(
                                "p",
                                {
                                    className:
                                        "mt-2 text-red-400 text-red-500 text-center",
                                },
                                "No Holy oils to apply. Craft some using alchemy!",
                            )
                          : null,
                ),
            ),
            React.createElement(
                "div",
                { className: "m-auto w-1/2 md:relative left-[-20px]" },
                this.state.applying_oil
                    ? React.createElement(LoadingProgressBar, null)
                    : null,
                this.state.error_message !== null
                    ? React.createElement(
                          DangerAlert,
                          { additional_css: "my-4" },
                          this.state.error_message,
                      )
                    : null,
            ),
            React.createElement(
                "div",
                { className: "text-center md:ml-[-100px] mt-3 mb-3" },
                React.createElement(PrimaryButton, {
                    button_label: "Apply Oil",
                    on_click: this.applyHolyOil.bind(this),
                    disabled: this.isApplyButtonDisabled(),
                }),
                React.createElement(DangerButton, {
                    button_label: "Remove",
                    on_click: this.clearCrafting.bind(this),
                    additional_css: "ml-2",
                    disabled: this.state.loading || this.props.cannot_craft,
                }),
                React.createElement(
                    "a",
                    {
                        href: "/information/holy-items",
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
    return WorkBench;
})(React.Component);
export default WorkBench;
//# sourceMappingURL=work-bench.js.map
