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
import DangerButton from "../../../../../ui/buttons/danger-button";
import { startCase } from "lodash";
import Ajax from "../../../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../../ui/progress-bars/loading-progress-bar";
var HealthBreakDown = (function (_super) {
    __extends(HealthBreakDown, _super);
    function HealthBreakDown(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            error_message: null,
            is_loading: true,
            details: null,
        };
        return _this;
    }
    HealthBreakDown.prototype.componentDidMount = function () {
        var _this = this;
        this.setState(
            {
                error_message: "",
            },
            function () {
                if (_this.props.character === null) {
                    return;
                }
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            _this.props.character_id +
                            "/specific-attribute-break-down",
                    )
                    .setParameters({
                        type: _this.props.type,
                        is_voided: _this.props.is_voided ? 1 : 0,
                    })
                    .doAjaxCall(
                        "get",
                        function (response) {
                            _this.setState({
                                is_loading: false,
                                details: response.data.break_down,
                            });
                        },
                        function (error) {
                            _this.setState({ is_loading: false });
                            if (typeof error.response !== "undefined") {
                                _this.setState({
                                    error_message: error.response.data.mmessage,
                                });
                            }
                        },
                    );
            },
        );
    };
    HealthBreakDown.prototype.titelizeType = function () {
        return startCase(this.props.type.replace("-", " "));
    };
    HealthBreakDown.prototype.renderClassSpecialtiesStatIncrease = function () {
        if (this.state.details === null) {
            return;
        }
        if (this.state.details.class_specialties === null) {
            return null;
        }
        return this.state.details.class_specialties.map(
            function (classSpecialty) {
                return React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-sky-600 dark:text-sky-500" },
                        classSpecialty.name,
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (classSpecialty.amount * 100).toFixed(2),
                        "%)",
                    ),
                );
            },
        );
    };
    HealthBreakDown.prototype.render = function () {
        if (this.state.loading || this.state.details === null) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "flex justify-between" },
                React.createElement(
                    "h3",
                    { className: "mr-2" },
                    (this.props.is_voided ? "Voided " : "") +
                        startCase(this.props.type.replace("-", " ")),
                ),
                React.createElement(DangerButton, {
                    button_label: "Close",
                    on_click: this.props.close_section,
                }),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            this.props.is_voided
                ? React.createElement(
                      "p",
                      { className: "my-4 text-blue-700 dark:text-blue-500" },
                      "Your modded dur, when voided, is based off a few other aspects such as equipment with out affixes, class specialties and other minor factors. This only comes into effect when the enemy voids you in combat.",
                  )
                : null,
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement("h4", null, "Stat Modifiers"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    React.createElement(
                        "ul",
                        {
                            className:
                                "space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400",
                        },
                        React.createElement(
                            "span",
                            { className: "text-slate-700 dark:text-slate-400" },
                            "Durability (Modded Dur)",
                        ),
                        " ",
                        React.createElement(
                            "span",
                            { className: "text-green-700 dark:text-green-500" },
                            "(+",
                            this.state.details.stat_amount,
                            ")",
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2 block md:hidden",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        null,
                        " ",
                        "Equipped Class Specials That Raise:",
                        " ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.class_specialties !== null
                        ? React.createElement(
                              "ol",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400",
                              },
                              this.renderClassSpecialtiesStatIncrease(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "You have nothing equipped.",
                          ),
                ),
            ),
        );
    };
    return HealthBreakDown;
})(React.Component);
export default HealthBreakDown;
//# sourceMappingURL=health-break-down.js.map
