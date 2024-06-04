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
import SuccessButton from "../../../game/components/ui/buttons/success-button";
import SuccessAlert from "../../../game/components/ui/alerts/simple-alerts/success-alert";
import ShopAjax, { SHOP_ACTIONS } from "./ajax/shop-ajax";
import { shopServiceContainer } from "./container/shop-container";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import InfoAlert from "../../../game/components/ui/alerts/simple-alerts/info-alert";
import { formatNumber } from "../../../game/lib/game/format-number";
import PrimaryOutlineButton from "../../../game/components/ui/buttons/primary-outline-button";
var BuyMultiple = (function (_super) {
    __extends(BuyMultiple, _super);
    function BuyMultiple(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            success_message: null,
            error_message: null,
            amount: 1,
            cost: 0,
        };
        _this.ajax = shopServiceContainer().fetch(ShopAjax);
        return _this;
    }
    BuyMultiple.prototype.componentDidMount = function () {
        this.setState({
            cost: this.props.item.cost,
        });
    };
    BuyMultiple.prototype.handleSettingAmount = function (event) {
        this.setState({
            error_message: null,
            success_message: null,
        });
        var amount = parseInt(event.target.value) || 0;
        var cost = amount * this.props.item.cost;
        if (this.props.is_merchant) {
            cost = cost - cost * 0.25;
        }
        if (cost > this.props.character_gold) {
            return this.setState({
                amount: amount,
                error_message: "You cannot afford this many.",
            });
        }
        var newInventoryCount = amount + this.props.inventory_count;
        if (newInventoryCount > this.props.inventory_max) {
            return this.setState({
                amount: amount,
                error_message: "You cannot fit this many in your bag.",
            });
        }
        this.setState({
            cost: cost,
            amount: amount,
        });
    };
    BuyMultiple.prototype.purchase = function () {
        var _this = this;
        this.setState(
            {
                success_message: null,
                error_message: null,
            },
            function () {
                _this.ajax.doShopAction(_this, SHOP_ACTIONS.BUY_MANY, {
                    item_id: _this.props.item.id,
                    amount: _this.state.amount,
                });
                _this.setState({
                    cost: _this.props.item.cost * 1,
                    amount: 1,
                });
            },
        );
    };
    BuyMultiple.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      { additional_css: "my-4" },
                      this.state.success_message,
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      this.state.error_message,
                  )
                : null,
            this.props.is_merchant
                ? React.createElement(
                      InfoAlert,
                      { additional_css: "my-4" },
                      "As a merchant you will receive a 25% discount on purchasing multiple items.",
                  )
                : null,
            React.createElement(PrimaryOutlineButton, {
                button_label: "Back to shop",
                on_click: this.props.close_view_buy_many,
            }),
            React.createElement(
                BasicCard,
                { additionalClasses: "my-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h3",
                        null,
                        "Purchase multiple of: ",
                        this.props.item.name,
                    ),
                ),
                React.createElement(
                    "div",
                    { className: "md:w-3/5 w-full my-4" },
                    React.createElement(
                        "div",
                        { className: "grid md:grid-cols-2 gap-4 my-4" },
                        React.createElement(
                            "div",
                            { className: "flex items-center" },
                            React.createElement(
                                "div",
                                { className: "mr-2" },
                                "Amount:",
                            ),
                            React.createElement("input", {
                                type: "number",
                                className:
                                    "w-full h-9 text-gray-800 dark:text-white border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-gray-200 dark:bg-gray-700 px-4",
                                value: this.state.amount,
                                onChange: this.handleSettingAmount.bind(this),
                            }),
                        ),
                        React.createElement(
                            "div",
                            null,
                            React.createElement(SuccessButton, {
                                button_label: "Purchase",
                                on_click: this.purchase.bind(this),
                            }),
                        ),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement("strong", null, "Cost:"),
                    " ",
                    formatNumber(this.state.cost),
                    " ",
                    "Gold ",
                    React.createElement("strong", null, "For Amount:"),
                    " ",
                    formatNumber(this.state.amount),
                ),
            ),
        );
    };
    return BuyMultiple;
})(React.Component);
export default BuyMultiple;
//# sourceMappingURL=buy-multiple.js.map
