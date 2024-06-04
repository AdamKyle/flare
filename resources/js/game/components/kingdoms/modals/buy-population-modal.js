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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import Ajax from "../../../lib/ajax/ajax";
var BuyPopulationModal = (function (_super) {
    __extends(BuyPopulationModal, _super);
    function BuyPopulationModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            total: "",
            error_message: "",
            cost: 0,
        };
        return _this;
    }
    BuyPopulationModal.prototype.setAmount = function (e) {
        var totalAmount = parseInt(e.target.value, 10);
        if (totalAmount > 2000000000) {
            totalAmount = 2000000000;
        }
        this.setState({
            total: totalAmount,
            cost: totalAmount * 5,
            error_message: "",
        });
    };
    BuyPopulationModal.prototype.buyPop = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setParameters({
                        amount_to_purchase: _this.state.total,
                    })
                    .setRoute(
                        "kingdoms/purchase-people/" + _this.props.kingdom.id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.handle_close();
                                },
                            );
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                var message = response.data.message;
                                if (response.data.error) {
                                    message = response.data.error;
                                }
                                _this.setState({
                                    loading: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    };
    BuyPopulationModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Buy Population",
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    handle_action: this.buyPop.bind(this),
                    secondary_button_disabled:
                        this.state.total === 0 ||
                        this.props.gold === 0 ||
                        this.state.cost > this.props.gold ||
                        this.state.loading,
                    secondary_button_label: "Buy Population",
                },
            },
            React.createElement(
                "p",
                { className: "mt-4" },
                React.createElement("strong", null, "Caution:"),
                " You may buy a total of 2 billion people at a cost of 5 Gold person. However if, upon the hourly update of kingdoms, you have more then the maximum population your kingdom is allowed, The Old Man will be angry with you.",
            ),
            React.createElement(
                "p",
                { className: "my-4" },
                "The Old Man will first take the cost of 10,000 gold per additional person over the population limit for the kingdom from your kingdom treasury. No gold? He will take your the amount out of your Gold Bars rounded up. No Gold Bars? He will take the gold out of your pockets. Still no gold? He will destroy your kingdom.",
            ),
            React.createElement(
                "p",
                { className: "my-4" },
                React.createElement(
                    "strong",
                    null,
                    "Do not buy more then you can use.",
                ),
            ),
            React.createElement(
                "div",
                { className: "flex items-center mb-5" },
                React.createElement(
                    "label",
                    { className: "w-1/2" },
                    "Population To Buy",
                ),
                React.createElement(
                    "div",
                    { className: "w-1/2" },
                    React.createElement("input", {
                        type: "number",
                        value: this.state.total,
                        onChange: this.setAmount.bind(this),
                        className: "form-control",
                        disabled: this.state.loading,
                    }),
                ),
            ),
            React.createElement(
                "div",
                { className: "my-4" },
                React.createElement(
                    "dl",
                    null,
                    React.createElement("dt", null, "Gold on hand:"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.props.gold),
                    ),
                    React.createElement("dt", null, "Total Cost:"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.state.cost),
                    ),
                ),
            ),
            this.state.error_message !== ""
                ? React.createElement(
                      DangerAlert,
                      null,
                      this.state.error_message,
                  )
                : null,
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return BuyPopulationModal;
})(React.Component);
export default BuyPopulationModal;
//# sourceMappingURL=buy-population-modal.js.map
