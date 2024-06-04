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
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import { formatNumber } from "../../../lib/game/format-number";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import Ajax from "../../../lib/ajax/ajax";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import WarningAlert from "../../../components/ui/alerts/simple-alerts/warning-alert";
import { parseInt } from "lodash";
var GoblinBankModal = (function (_super) {
    __extends(GoblinBankModal, _super);
    function GoblinBankModal(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                key: "deposit",
                name: "Deposit",
            },
            {
                key: "withdrawal",
                name: "withdrawal",
            },
        ];
        _this.state = {
            amount_to_withdraw: "",
            amount_to_deposit: "",
            cost_to_deposit: 0,
            gold_gained: 0,
            error_message: "",
            success_message: "",
            loading: false,
        };
        return _this;
    }
    GoblinBankModal.prototype.setAmountToWithdraw = function (e) {
        var value = parseInt(e.target.value, 10) || 0;
        if (value === 0) {
            return this.setState({
                success_message: "",
                error_message: "",
                amount_to_withdraw: "",
                gold_gained: 0,
            });
        }
        if (value > 1000) {
            value === 1000;
        }
        if (value > this.props.gold_bars) {
            value = this.props.gold_bars;
        }
        this.setState({
            success_message: "",
            error_message: "",
            amount_to_withdraw: value,
            gold_gained: value * 2000000000,
        });
    };
    GoblinBankModal.prototype.setAmountToDeposit = function (e) {
        var value = parseInt(e.target.value, 10) || 0;
        this.setAmount(value);
    };
    GoblinBankModal.prototype.setAmount = function (value) {
        if (value === 0) {
            return this.setState({
                success_message: "",
                error_message: "",
                amount_to_deposit: "",
                cost_to_deposit: 0,
            });
        }
        if (value > 1000) {
            value = 1000;
        }
        var newTotal = this.props.gold_bars + value;
        if (newTotal > 1000) {
            value = this.props.gold_bars - this.props.gold_bars;
        }
        this.setState({
            success_message: "",
            error_message: "",
            amount_to_deposit: value,
            cost_to_deposit: value * 2000000000,
        });
    };
    GoblinBankModal.prototype.closeSuccess = function () {
        this.setState({
            success_message: "",
        });
    };
    GoblinBankModal.prototype.withdraw = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                error_message: "",
                success_message: "",
            },
            function () {
                new Ajax()
                    .setParameters({
                        amount_to_withdraw: _this.state.amount_to_withdraw,
                    })
                    .setRoute(
                        "kingdoms/withdraw-bars-as-gold/" +
                            _this.props.kingdom_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message: result.data.message,
                                amount_to_deposit: "",
                                amount_to_withdraw: "",
                            });
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
                            console.error(error);
                        },
                    );
            },
        );
    };
    GoblinBankModal.prototype.deposit = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                error_message: "",
                success_message: "",
            },
            function () {
                new Ajax()
                    .setParameters({
                        amount_to_purchase: _this.state.amount_to_deposit,
                    })
                    .setRoute(
                        "kingdoms/purchase-gold-bars/" + _this.props.kingdom_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message: result.data.message,
                                amount_to_deposit: "",
                                amount_to_withdraw: "",
                            });
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                if (response.status === 422) {
                                    _this.setState({
                                        error_message: response.data.message,
                                    });
                                }
                            }
                            console.error(error);
                        },
                    );
            },
        );
    };
    GoblinBankModal.prototype.isInputDisabled = function (amount) {
        if (this.props.goblin_bank.level < 5) {
            return true;
        }
        return amount === 0;
    };
    GoblinBankModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Goblin Bank",
                primary_button_disabled: this.state.loading,
            },
            this.props.goblin_bank.level < 5
                ? React.createElement(
                      WarningAlert,
                      { additional_css: "my-4" },
                      "You need to level the Goblin Bank to level 5 before being able to use the bank.",
                  )
                : null,
            React.createElement(
                Tabs,
                { tabs: this.tabs, disabled: this.state.loading },
                React.createElement(
                    TabPanel,
                    { key: "deposit" },
                    React.createElement(
                        InfoAlert,
                        null,
                        "Cost to buy is 2 Billion Gold per Rune. You may have a total of 1000 Gold Bars.",
                    ),
                    React.createElement(
                        "div",
                        { className: "flex items-center my-4" },
                        React.createElement(
                            "label",
                            { className: "w-1/2" },
                            "Amount to deposit",
                        ),
                        React.createElement(
                            "div",
                            { className: "w-1/2" },
                            React.createElement("input", {
                                type: "number",
                                value: this.state.amount_to_deposit,
                                onChange: this.setAmountToDeposit.bind(this),
                                className: "form-control",
                                disabled: this.isInputDisabled(
                                    this.props.character_gold,
                                ),
                            }),
                        ),
                    ),
                    React.createElement(
                        "dl",
                        { className: "my-4" },
                        React.createElement("dt", null, "Current Gold Bars"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.gold_bars),
                        ),
                        React.createElement("dt", null, "Current Gold"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.character_gold),
                        ),
                        React.createElement("dt", null, "Cost in Gold"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.state.cost_to_deposit),
                        ),
                    ),
                    React.createElement(PrimaryButton, {
                        button_label: "Deposit Amount",
                        on_click: this.deposit.bind(this),
                        disabled:
                            this.state.amount_to_deposit === "" ||
                            this.props.character_gold === 0 ||
                            (typeof this.state.amount_to_deposit === "number" &&
                                this.state.amount_to_deposit <= 0) ||
                            (typeof this.state.cost_to_deposit === "number" &&
                                this.props.character_gold <
                                    this.state.cost_to_deposit),
                    }),
                ),
                React.createElement(
                    TabPanel,
                    { key: "withdrawal" },
                    React.createElement(
                        "div",
                        { className: "flex items-center my-4" },
                        React.createElement(
                            "label",
                            { className: "w-1/2" },
                            "Amount to withdraw",
                        ),
                        React.createElement(
                            "div",
                            { className: "w-1/2" },
                            React.createElement("input", {
                                type: "number",
                                value: this.state.amount_to_withdraw,
                                onChange: this.setAmountToWithdraw.bind(this),
                                className: "form-control",
                                disabled: this.isInputDisabled(
                                    this.props.gold_bars,
                                ),
                            }),
                        ),
                    ),
                    React.createElement(
                        "dl",
                        { className: "my-4" },
                        React.createElement("dt", null, "Current Gold Bars"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.gold_bars),
                        ),
                        React.createElement("dt", null, "Gold to gain"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.state.gold_gained),
                        ),
                    ),
                    React.createElement(PrimaryButton, {
                        button_label: "Withdraw Amount",
                        on_click: this.withdraw.bind(this),
                        disabled:
                            this.state.amount_to_withdraw === "" ||
                            this.props.gold_bars <= 0,
                    }),
                ),
            ),
            this.state.error_message !== ""
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      this.state.error_message,
                  )
                : null,
            this.state.success_message !== ""
                ? React.createElement(
                      SuccessAlert,
                      {
                          additional_css: "my-4",
                          close_alert: this.closeSuccess.bind(this),
                      },
                      this.state.success_message,
                  )
                : null,
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return GoblinBankModal;
})(React.Component);
export default GoblinBankModal;
//# sourceMappingURL=goblin-bank-modal.js.map
