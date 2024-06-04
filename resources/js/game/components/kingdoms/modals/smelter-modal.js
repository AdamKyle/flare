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
import Dialogue from "../../../components/ui/dialogue/dialogue";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import { formatNumber } from "../../../lib/game/format-number";
import Ajax from "../../../lib/ajax/ajax";
import clsx from "clsx";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import { DateTime } from "luxon";
var SmelterModal = (function (_super) {
    __extends(SmelterModal, _super);
    function SmelterModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: false,
            total: 0,
            error_message: "",
            time_estimate: 0,
            cost: 0,
        };
        return _this;
    }
    SmelterModal.prototype.getTimeLeft = function () {
        var end = DateTime.fromISO(this.props.smelting_completed_at);
        var start = DateTime.now();
        var timeLeft = end.diff(start, "seconds").toObject();
        if (typeof timeLeft === "undefined") {
            return 0;
        }
        if (typeof timeLeft.seconds === "undefined") {
            return 0;
        }
        return parseInt(timeLeft.seconds.toFixed(0));
    };
    SmelterModal.prototype.setAmount = function (e) {
        var totalAmount = parseInt(e.target.value, 10) || 0;
        if (totalAmount > this.props.max_steel) {
            totalAmount = this.props.max_steel;
        }
        var timeAddition = (totalAmount / 100) >> 0;
        var totalTime = timeAddition * 5;
        this.setState({
            total: totalAmount,
            cost: totalAmount * 2,
            error_message:
                totalAmount < 100 && totalAmount > 0
                    ? "Can only smelt a minimum of 100 iron at a time."
                    : "",
            time_estimate: parseInt(
                (
                    totalTime -
                    totalTime * this.props.smelting_time_reduction
                ).toFixed(2),
            ),
        });
    };
    SmelterModal.prototype.smeltSteel = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setParameters({
                        amount_to_smelt: _this.state.total,
                    })
                    .setRoute("kingdoms/smelt-iron/" + _this.props.kingdom_id)
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {},
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
    SmelterModal.prototype.cancelSmelting = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdoms/cancel-smelting/" + _this.props.kingdom_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {},
                            );
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
                        },
                    );
            },
        );
    };
    SmelterModal.prototype.renderSmeltingOption = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "flex items-center mb-5" },
                React.createElement(
                    "label",
                    { className: "w-1/2" },
                    "Iron to smelt",
                ),
                React.createElement(
                    "div",
                    { className: "w-1/2" },
                    React.createElement("input", {
                        type: "number",
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
                    React.createElement("dt", null, "Iron on hand:"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.props.iron),
                    ),
                    React.createElement("dt", null, "Iron Cost:"),
                    React.createElement(
                        "dd",
                        {
                            className: clsx({
                                "text-red-500 dark:text-red-400":
                                    this.state.cost > this.props.iron,
                            }),
                        },
                        formatNumber(this.state.cost),
                    ),
                    React.createElement("dt", null, "Time Estimate:"),
                    React.createElement(
                        "dd",
                        null,
                        formatNumber(this.state.time_estimate),
                        " Minutes",
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
    SmelterModal.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Smelt Steel",
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    handle_action: this.smeltSteel.bind(this),
                    secondary_button_disabled:
                        this.state.total === 0 ||
                        this.state.total < 100 ||
                        this.props.iron === 0 ||
                        this.state.cost > this.props.iron ||
                        this.state.loading,
                    secondary_button_label: "Smelt Steel",
                },
            },
            this.props.smelting_time_left > 0
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "p",
                          { className: "my-4" },
                          "Canceling this smelting request will only give you a percentage of iron back based on the time elapsed.",
                      ),
                      React.createElement(TimerProgressBar, {
                          time_remaining: this.getTimeLeft(),
                          time_out_label:
                              "Smelting Steel: " +
                              formatNumber(this.props.smelting_amount),
                      }),
                      React.createElement(DangerButton, {
                          button_label: "Stop Smelting",
                          on_click: this.cancelSmelting.bind(this),
                          additional_css: "my-4",
                      }),
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
                  )
                : this.renderSmeltingOption(),
        );
    };
    return SmelterModal;
})(React.Component);
export default SmelterModal;
//# sourceMappingURL=smelter-modal.js.map
