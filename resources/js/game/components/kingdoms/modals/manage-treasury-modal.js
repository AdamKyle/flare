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
import Tabs from "../../../components/ui/tabs/tabs";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import { formatNumber } from "../../../lib/game/format-number";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
var ManageTreasuryModal = (function (_super) {
    __extends(ManageTreasuryModal, _super);
    function ManageTreasuryModal(props) {
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
            {
                key: "mass-embezzle",
                name: "Mass Embezzle",
            },
        ];
        _this.state = {
            amount_to_withdraw: "",
            amount_to_deposit: "",
            loading: false,
            success_message: "",
            error_message: "",
        };
        return _this;
    }
    ManageTreasuryModal.prototype.setAmountToDeposit = function (e) {
        var value = parseInt(e.target.value, 10) || 0;
        if (value === 0) {
            return this.setState({
                success_message: "",
                error_message: "",
                amount_to_deposit: "",
            });
        }
        if (value > 2000000000) {
            value = 2000000000;
        }
        var newValue = value + this.props.treasury;
        if (newValue > 2000000000) {
            value = 2000000000 - this.props.treasury;
        }
        if (value > this.props.character_gold) {
            value = this.props.character_gold;
        }
        if (value === 0) {
            this.setState({
                success_message: "",
                error_message: "",
                amount_to_deposit: "",
            });
            return;
        }
        this.setState({
            success_message: "",
            error_message: "",
            amount_to_deposit: value,
        });
    };
    ManageTreasuryModal.prototype.setAmountToWithdraw = function (e) {
        var value = parseInt(e.target.value, 10) || 0;
        if (value === 0) {
            return this.setState({
                success_message: "",
                error_message: "",
                amount_to_withdraw: "",
            });
        }
        if (value > 2000000000) {
            value = 2000000000;
        }
        if (value > this.props.character_gold) {
            value = value - this.props.character_gold;
            if (value <= 0) {
                value = 0;
            }
        }
        return this.setState({
            success_message: "",
            error_message:
                value === 0 ? "This would cost gold wastage. Not allowed." : "",
            amount_to_withdraw: value === 0 ? "" : value,
        });
    };
    ManageTreasuryModal.prototype.closeSuccess = function () {
        this.setState({
            success_message: "",
        });
    };
    ManageTreasuryModal.prototype.deposit = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute("kingdoms/deposit/" + _this.props.kingdom_id)
                    .setParameters({
                        deposit_amount: _this.state.amount_to_deposit,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message: result.data.message,
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
    ManageTreasuryModal.prototype.withdraw = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute("kingdoms/embezzle/" + _this.props.kingdom_id)
                    .setParameters({
                        embezzle_amount: _this.state.amount_to_withdraw,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message: result.data.message,
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
    ManageTreasuryModal.prototype.massEmbezzle = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdoms/mass-embezzle/" + _this.props.character_id,
                    )
                    .setParameters({
                        embezzle_amount: _this.state.amount_to_withdraw,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message: result.data.message,
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
    ManageTreasuryModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Manage Treasury",
                primary_button_disabled: this.state.loading,
            },
            React.createElement(
                Tabs,
                { tabs: this.tabs, disabled: this.state.loading },
                React.createElement(
                    TabPanel,
                    { key: "deposit" },
                    React.createElement(
                        InfoAlert,
                        null,
                        "Depositing Gold can increase the Morale by 5% per deposit. The minimum amount to gain 5% increase in morale is 10,000,000 Gold. Kingdoms can hold a maximum of 2 billion gold. Depositing gold also increases your treasury defence which increases your over all kingdom defence.",
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
                                onChange: this.setAmountToDeposit.bind(this),
                                className: "form-control",
                                disabled: this.props.character_gold === 0,
                            }),
                        ),
                    ),
                    React.createElement(
                        "dl",
                        { className: "my-4" },
                        React.createElement("dt", null, "Current Treasury"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.treasury),
                        ),
                        React.createElement("dt", null, "Current Morale"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.morale * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Current Gold"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.character_gold),
                        ),
                    ),
                    React.createElement(PrimaryButton, {
                        button_label: "Deposit Amount",
                        on_click: this.deposit.bind(this),
                        disabled:
                            this.state.amount_to_deposit === "" ||
                            this.props.character_gold === 0 ||
                            (typeof this.state.amount_to_deposit === "number" &&
                                this.props.character_gold <
                                    this.state.amount_to_deposit),
                    }),
                ),
                React.createElement(
                    TabPanel,
                    { key: "withdrawal" },
                    React.createElement(
                        InfoAlert,
                        null,
                        "Withdrawing, or Embezzling, will reduce the morale of the kingdom by 15%, regardless of amount taken. If a kingdom has 15% or less morale, you cannot withdraw gold. Withdrawing gold also reduces your treasury defence, which reduces your overall kingdom defence.",
                    ),
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
                                onChange: this.setAmountToWithdraw.bind(this),
                                className: "form-control",
                                disabled:
                                    this.props.treasury === 0 ||
                                    this.props.morale <= 0.15,
                            }),
                        ),
                    ),
                    React.createElement(
                        "dl",
                        { className: "my-4" },
                        React.createElement("dt", null, "Current Treasury"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.treasury),
                        ),
                        React.createElement("dt", null, "Current Morale"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.morale * 100).toFixed(2),
                            "%",
                        ),
                    ),
                    React.createElement(PrimaryButton, {
                        button_label: "Withdraw Amount",
                        on_click: this.withdraw.bind(this),
                        disabled:
                            this.state.amount_to_withdraw === "" ||
                            this.props.treasury === 0 ||
                            this.props.morale <= 0.15,
                    }),
                ),
                React.createElement(
                    TabPanel,
                    { key: "mass-embezzle" },
                    React.createElement(
                        "p",
                        { className: "my-4" },
                        "Mass embezzling can be done from any kingdom, but only applies to all kingdoms on the current plane.",
                    ),
                    React.createElement(
                        "p",
                        { className: "my-4" },
                        "Each kingdom that gets embezzled from will reduce it's morale by 15%. Low morale kingdoms and kingdoms with no treasury will be skipped. Each kingdom that can be embezzled from will also loose Treasury Defence, which in turn reduces your over all kingdom defence.",
                    ),
                    React.createElement(
                        "p",
                        { className: "my-4" },
                        "Your server message section, below, will update to show which kingdoms you embezzled from and how much as well as morale reductions.",
                    ),
                    React.createElement(
                        "p",
                        { className: "my-4" },
                        "Finally, if you have not completed the Goblins Lust for Gold",
                        " ",
                        React.createElement(
                            "a",
                            { href: "/information/quests", target: "_blank" },
                            "quest",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                        " ",
                        "you will not be able to embezzle and be told as such.",
                    ),
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
                                onChange: this.setAmountToWithdraw.bind(this),
                                className: "form-control",
                                disabled: this.props.treasury === 0,
                            }),
                        ),
                    ),
                    React.createElement(
                        "dl",
                        { className: "my-4" },
                        React.createElement("dt", null, "Current Treasury"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.treasury),
                        ),
                    ),
                    React.createElement(PrimaryButton, {
                        button_label: "Mass Embezzle",
                        on_click: this.massEmbezzle.bind(this),
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
    return ManageTreasuryModal;
})(React.Component);
export default ManageTreasuryModal;
//# sourceMappingURL=manage-treasury-modal.js.map
