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
import Ajax from "../../../lib/ajax/ajax";
import DangerButton from "../../ui/buttons/danger-button";
import PrimaryButton from "../../ui/buttons/primary-button";
import Select from "react-select";
import { formatNumber } from "../../../lib/game/format-number";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import CraftingXp from "../base-components/skill-xp/crafting-xp";
var GemCrafting = (function (_super) {
    __extends(GemCrafting, _super);
    function GemCrafting(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            tiersForCrafting: [],
            selectedTier: 0,
            tierCost: {
                copper_coin_cost: 0,
                shards_cost: 0,
                gold_dust_cost: 0,
            },
            errorMessage: null,
            loading: true,
            isCrafting: false,
        };
        return _this;
    }
    GemCrafting.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("gem-crafting/craftable-tiers/" + this.props.character_id)
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        tiersForCrafting: result.data.tiers,
                        skill_xp: result.data.skill_xp,
                        loading: false,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    GemCrafting.prototype.craft = function () {
        var _this = this;
        if (this.state.selectedTier === 0) {
            return this.setState({
                error_message: "Please select a tier.",
            });
        }
        this.setState(
            {
                isCrafting: true,
                errorMessage: null,
            },
            function () {
                new Ajax()
                    .setRoute("gem-crafting/craft/" + _this.props.character_id)
                    .setParameters({
                        tier: _this.state.selectedTier,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                tiersForCrafting: result.data.tiers,
                                skill_xp: result.data.skill_xp,
                                isCrafting: false,
                            });
                        },
                        function (error) {
                            _this.setState(
                                {
                                    isCrafting: false,
                                },
                                function () {
                                    if (typeof error.response !== "undefined") {
                                        var response = error.response;
                                        _this.setState({
                                            errorMessage: response.data.message,
                                        });
                                    }
                                },
                            );
                        },
                    );
            },
        );
    };
    GemCrafting.prototype.setTierToCraft = function (data) {
        var _this = this;
        if (data.value === 0) {
            this.setState({
                selectedTier: 0,
                tierCost: {
                    copper_coin_cost: 0,
                    shards_cost: 0,
                    gold_dust_cost: 0,
                },
            });
            return;
        }
        this.setState(
            {
                selectedTier: data.value,
            },
            function () {
                var tierData = _this.state.tiersForCrafting[data.value - 1];
                var cost = {
                    copper_coin_cost: tierData.cost.copper_coins,
                    shards_cost: tierData.cost.shards,
                    gold_dust_cost: tierData.cost.gold_dust,
                };
                _this.setState({
                    tierCost: cost,
                });
            },
        );
    };
    GemCrafting.prototype.craftingTiers = function () {
        var tierForSelection = this.state.tiersForCrafting.map(
            function (tier, index) {
                return {
                    label: "Gem Tier " + (index + 1),
                    value: index + 1,
                };
            },
        );
        tierForSelection.splice(0, 0, {
            label: "Please select tier",
            value: 0,
        });
        return tierForSelection;
    };
    GemCrafting.prototype.craftingTierSelected = function () {
        if (this.state.selectedTier === 0) {
            return {
                label: "Please Select",
                value: 0,
            };
        }
        return {
            label: "Gem Tier " + this.state.selectedTier,
            value: this.state.selectedTier,
        };
    };
    GemCrafting.prototype.render = function () {
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
                    this.state.loading
                        ? React.createElement(LoadingProgressBar, null)
                        : React.createElement(
                              Fragment,
                              null,
                              React.createElement(Select, {
                                  onChange: this.setTierToCraft.bind(this),
                                  options: this.craftingTiers(),
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
                                  value: this.craftingTierSelected(),
                              }),
                              this.state.selectedTier !== 0
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-4 mb-2" },
                                        React.createElement(
                                            "dl",
                                            null,
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Gold Dust Cost",
                                            ),
                                            React.createElement(
                                                "dl",
                                                null,
                                                formatNumber(
                                                    this.state.tierCost
                                                        .gold_dust_cost,
                                                ),
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Shards Cost",
                                            ),
                                            React.createElement(
                                                "dl",
                                                null,
                                                formatNumber(
                                                    this.state.tierCost
                                                        .shards_cost,
                                                ),
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Copper Coin Cost",
                                            ),
                                            React.createElement(
                                                "dl",
                                                null,
                                                formatNumber(
                                                    this.state.tierCost
                                                        .copper_coin_cost,
                                                ),
                                            ),
                                        ),
                                    )
                                  : null,
                              this.state.isCrafting
                                  ? React.createElement(
                                        LoadingProgressBar,
                                        null,
                                    )
                                  : null,
                              this.state.tiersForCrafting.length > 0
                                  ? React.createElement(
                                        "div",
                                        { className: "my-4" },
                                        React.createElement(CraftingXp, {
                                            skill_xp: this.state.skill_xp,
                                        }),
                                    )
                                  : null,
                          ),
                ),
            ),
            this.state.errorMessage !== null
                ? React.createElement(
                      "div",
                      {
                          className:
                              "mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]",
                      },
                      React.createElement(
                          "div",
                          { className: "lg:cols-start-1 lg:col-span-2" },
                          React.createElement(
                              DangerAlert,
                              null,
                              this.state.errorMessage,
                          ),
                      ),
                  )
                : null,
            React.createElement(
                "div",
                { className: "text-center lg:ml-[-100px] mt-3 mb-3" },
                React.createElement(PrimaryButton, {
                    button_label: "Craft",
                    on_click: this.craft.bind(this),
                    disabled:
                        this.state.loading ||
                        this.state.selected_item === null ||
                        this.props.cannot_craft ||
                        this.state.isCrafting,
                }),
                React.createElement(DangerButton, {
                    button_label: "Close",
                    on_click: this.props.remove_crafting,
                    additional_css: "ml-2",
                    disabled:
                        this.state.loading ||
                        this.props.cannot_craft ||
                        this.state.isCrafting,
                }),
                React.createElement(
                    "a",
                    {
                        href: "/information/gem-crafting",
                        target: "_blank",
                        className: "relative top-[0px] ml-2",
                    },
                    "Help ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return GemCrafting;
})(React.Component);
export default GemCrafting;
//# sourceMappingURL=gem-crafting.js.map
