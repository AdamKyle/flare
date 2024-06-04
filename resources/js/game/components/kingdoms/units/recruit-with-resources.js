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
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import DangerButton from "../../../components/ui/buttons/danger-button";
import { parseInt } from "lodash";
import Ajax from "../../../lib/ajax/ajax";
var RecruitWithResources = (function (_super) {
    __extends(RecruitWithResources, _super);
    function RecruitWithResources(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            success_message: null,
            error_message: null,
            amount_to_recruit: "",
            loading: false,
            show_time_help: false,
            cost_in_gold: 0,
        };
        return _this;
    }
    RecruitWithResources.prototype.recruitUnits = function () {
        var _this = this;
        this.setState(
            {
                error_message: null,
                success_message: null,
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "kingdoms/" +
                            _this.props.kingdom_id +
                            "/recruit-units/" +
                            _this.props.unit.id,
                    )
                    .setParameters({
                        amount:
                            _this.state.amount_to_recruit === ""
                                ? 1
                                : _this.state.amount_to_recruit,
                        recruitment_type: "resources",
                    })
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState({
                                loading: false,
                                success_message: response.data.message,
                                amount_to_recruit: "",
                                show_time_help: false,
                                cost_in_gold: 0,
                                time_needed: 0,
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    loading: false,
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    RecruitWithResources.prototype.setResourceAmount = function (e) {
        if (typeof this.props.unit_cost_reduction === "undefined") {
            this.setState({
                error_message:
                    "Cannot determine cost. Unit Cost Reduction Is Undefined.",
            });
            return;
        }
        var value = parseInt(e.target.value) || 0;
        if (value === 0) {
            return this.setState({
                amount_to_recruit: "",
            });
        }
        var amount = this.getAmountToRecruit(value);
        if (amount === 0) {
            this.props.set_resource_amount(0, 0);
            return;
        }
        var timeNeeded = this.props.unit.time_to_recruit * amount;
        timeNeeded =
            timeNeeded - timeNeeded * this.props.kingdom_unit_time_reduction;
        this.props.set_resource_amount(amount, timeNeeded);
        this.setState({
            amount_to_recruit: amount,
        });
    };
    RecruitWithResources.prototype.getAmountToRecruit = function (
        numberToRecruit,
    ) {
        if (numberToRecruit === 0) {
            return 0;
        }
        numberToRecruit = Math.abs(numberToRecruit);
        var currentMax = this.props.unit.max_amount;
        if (numberToRecruit > currentMax) {
            numberToRecruit = currentMax;
        }
        return numberToRecruit;
    };
    RecruitWithResources.prototype.getAmount = function () {
        return parseInt(this.state.amount_to_recruit) || 1;
    };
    RecruitWithResources.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      { additional_css: "mb-5" },
                      this.state.success_message,
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "mb-5" },
                      this.state.error_message,
                  )
                : null,
            React.createElement(
                "div",
                { className: "flex items-center mb-5" },
                React.createElement(
                    "label",
                    { className: "w-[50px] mr-4" },
                    "Amount",
                ),
                React.createElement(
                    "div",
                    { className: "w-2/3" },
                    React.createElement("input", {
                        type: "text",
                        value: this.state.amount_to_recruit,
                        onChange: this.setResourceAmount.bind(this),
                        className: "form-control",
                        disabled: this.state.loading,
                    }),
                ),
            ),
            this.state.loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
            React.createElement(PrimaryButton, {
                button_label: "Recruit Units",
                additional_css: "mr-2",
                on_click: this.recruitUnits.bind(this),
                disabled:
                    this.state.amount_to_recruit <= 0 || this.state.loading,
            }),
            React.createElement(DangerButton, {
                button_label: "Cancel",
                on_click: this.props.remove_selection.bind(this),
                disabled: this.state.loading,
            }),
        );
    };
    return RecruitWithResources;
})(React.Component);
export default RecruitWithResources;
//# sourceMappingURL=recruit-with-resources.js.map
