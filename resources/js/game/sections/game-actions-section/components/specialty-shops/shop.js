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
import Ajax from "../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import Select from "react-select";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import DangerButton from "../../../../components/ui/buttons/danger-button";
import { formatNumber } from "../../../../lib/game/format-number";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
var Shop = (function (_super) {
    __extends(Shop, _super);
    function Shop(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            fetching: true,
            loading: false,
            item_selected: null,
            error_message: "",
            success_message: "",
            items: [],
            gold_dust_cost: 0,
            gold_cost: 0,
            copper_coin_cost: 0,
            shards_cost: 0,
        };
        return _this;
    }
    Shop.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("specialty-shop/" + this.props.character_id)
            .setParameters({
                type: this.props.type,
            })
            .doAjaxCall(
                "get",
                function (response) {
                    _this.setState({
                        fetching: false,
                        items: response.data.items,
                    });
                },
                function (error) {
                    _this.setState({
                        fetching: false,
                    });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    Shop.prototype.closeSection = function () {
        if (this.props.type === "Hell Forged") {
            this.props.close_hell_forged();
        }
        if (this.props.type === "Purgatory Chains") {
            this.props.close_purgatory_chains();
        }
        if (this.props.type === "Twisted Earth") {
            this.props.close_twisted_earth();
        }
    };
    Shop.prototype.setItemToBuy = function (data) {
        var foundItem = this.state.items.filter(function (item) {
            return item.id === data.value;
        });
        if (foundItem.length === 0) {
            return;
        }
        foundItem = foundItem[0];
        this.setState({
            item_selected: foundItem.id,
            gold_dust_cost:
                foundItem.gold_dust_cost !== null
                    ? foundItem.gold_dust_cost
                    : 0,
            gold_cost: foundItem.cost !== null ? foundItem.cost : 0,
            copper_coin_cost:
                foundItem.copper_coin_cost !== null
                    ? foundItem.copper_coin_cost
                    : 0,
            shards_cost:
                foundItem.shards_cost !== null ? foundItem.shards_cost : 0,
        });
    };
    Shop.prototype.getItemsToSelect = function () {
        return this.state.items.map(function (item) {
            return {
                label: item.name + " (" + item.type + ")",
                value: item.id,
            };
        });
    };
    Shop.prototype.defaultValue = function () {
        var _this = this;
        if (this.state.item_selected !== null) {
            var foundItem = this.state.items.filter(function (item) {
                return item.id === _this.state.item_selected;
            });
            if (foundItem.length !== 0) {
                foundItem = foundItem[0];
                return {
                    label: foundItem.name + " (" + foundItem.type + ")",
                    value: foundItem.id,
                };
            }
        }
        return {
            label: "Please select",
            value: 0,
        };
    };
    Shop.prototype.purchase = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                error_message: "",
                success_message: "",
            },
            function () {
                new Ajax()
                    .setRoute(
                        "specialty-shop/purchase/" + _this.props.character_id,
                    )
                    .setParameters({
                        type: _this.props.type,
                        item_id: _this.state.item_selected,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message:
                                    "Item has been purchased! Check server messages to see a link for the new item! For mobile players you can tap on Chat Tabs and select Server Messages.",
                            });
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    Shop.prototype.renderCost = function () {
        var costs = [];
        if (this.state.gold_cost !== 0) {
            costs.push(
                React.createElement(
                    Fragment,
                    null,
                    React.createElement("dt", null, "Gold Cost"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.state.gold_cost),
                    ),
                ),
            );
        }
        if (this.state.shards_cost !== 0) {
            costs.push(
                React.createElement(
                    Fragment,
                    null,
                    React.createElement("dt", null, "Shards Cost"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.state.shards_cost),
                    ),
                ),
            );
        }
        if (this.state.gold_dust_cost !== 0) {
            costs.push(
                React.createElement(
                    Fragment,
                    null,
                    React.createElement("dt", null, "Gold Dust Cost"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.state.gold_dust_cost),
                    ),
                ),
            );
        }
        if (this.state.copper_coin_cost !== 0) {
            costs.push(
                React.createElement(
                    Fragment,
                    null,
                    React.createElement("dt", null, "Copper Coin Cost"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.state.copper_coin_cost),
                    ),
                ),
            );
        }
        return costs;
    };
    Shop.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]" },
            React.createElement(
                "div",
                { className: "cols-start-1 col-span-2" },
                this.state.fetching
                    ? React.createElement(LoadingProgressBar, null)
                    : React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "div",
                              null,
                              React.createElement(Select, {
                                  onChange: this.setItemToBuy.bind(this),
                                  options: this.getItemsToSelect(),
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
                                  value: this.defaultValue(),
                              }),
                              this.state.error_message !== ""
                                  ? React.createElement(
                                        DangerAlert,
                                        { additional_css: "my-3" },
                                        this.state.error_message,
                                    )
                                  : null,
                              this.state.success_message !== ""
                                  ? React.createElement(
                                        SuccessAlert,
                                        { additional_css: "my-3" },
                                        this.state.success_message,
                                    )
                                  : null,
                              this.state.item_selected !== null
                                  ? React.createElement(
                                        "dl",
                                        { className: "my-3" },
                                        this.renderCost(),
                                    )
                                  : null,
                              this.state.loading
                                  ? React.createElement(
                                        LoadingProgressBar,
                                        null,
                                    )
                                  : null,
                              React.createElement(
                                  "div",
                                  {
                                      className:
                                          "text-center md:ml-[-100px] my-3",
                                  },
                                  React.createElement(PrimaryButton, {
                                      button_label: "Purchase Item",
                                      on_click: this.purchase.bind(this),
                                      disabled:
                                          this.state.loading ||
                                          this.state.item_selected === null ||
                                          this.props.is_dead,
                                  }),
                                  React.createElement(DangerButton, {
                                      button_label: "Close",
                                      on_click: this.closeSection.bind(this),
                                      additional_css: "ml-2",
                                      disabled:
                                          this.state.loading ||
                                          this.props.cannot_craft,
                                  }),
                                  React.createElement(
                                      "a",
                                      {
                                          href: "/information/gear-progression",
                                          target: "_blank",
                                          className: "ml-2",
                                      },
                                      "Help",
                                      " ",
                                      React.createElement("i", {
                                          className: "fas fa-external-link-alt",
                                      }),
                                  ),
                              ),
                          ),
                      ),
            ),
        );
    };
    return Shop;
})(React.Component);
export default Shop;
//# sourceMappingURL=shop.js.map
