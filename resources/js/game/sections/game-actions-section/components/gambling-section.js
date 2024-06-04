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
import Ajax from "../../../lib/ajax/ajax";
import SuccessButton from "../../../components/ui/buttons/success-button";
import { random } from "lodash";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import clsx from "clsx";
import SpinSection from "./gambling-section/spin-section";
import StationarySpinSection from "./gambling-section/stationary-spin-section";
import SuccessMessage from "./gambling-section/success-message";
import ErrorMessage from "./gambling-section/error-message";
var GamblingSection = (function (_super) {
    __extends(GamblingSection, _super);
    function GamblingSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            icons: [],
            spinning: false,
            spinningIndexes: [],
            roll: [],
            roll_message: null,
            error_message: null,
            timeoutFor: 0,
            cost: 1000000,
        };
        _this.gamblingTimeOut = Echo.private(
            "slot-timeout-" + _this.props.character.user_id,
        );
        return _this;
    }
    GamblingSection.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax().setRoute("character/gambler").doAjaxCall(
            "get",
            function (response) {
                _this.setState({
                    loading: false,
                    icons: response.data.icons,
                });
            },
            function (error) {
                console.error(error);
            },
        );
        this.gamblingTimeOut.listen(
            "Game.Gambler.Events.GamblerSlotTimeOut",
            function (event) {
                _this.setState({
                    timeoutFor: event.timeoutFor,
                });
            },
        );
    };
    GamblingSection.prototype.spin = function () {
        var _this = this;
        var gold = parseFloat(this.props.character.gold.replace(/,/g, ""));
        if (gold < this.state.cost) {
            this.setState({
                roll_message: null,
                error_message:
                    "You do not have the required gold to take a spin child.",
            });
            return;
        }
        this.setState(
            {
                spinning: true,
                roll_message: null,
                error_message: null,
            },
            function () {
                _this.spinning();
                setTimeout(function () {
                    _this.processRoll();
                }, 1000);
            },
        );
    };
    GamblingSection.prototype.spinning = function () {
        if (this.state.spinning) {
            var max_1 = this.state.icons.length - 1;
            var i = 0;
            var self_1 = this;
            while (i < 100) {
                (function (i) {
                    setTimeout(function () {
                        self_1.setState({
                            spinningIndexes: [
                                random(0, max_1),
                                random(0, max_1),
                                random(0, max_1),
                            ],
                        });
                    }, i * 300);
                })(i++);
            }
        }
    };
    GamblingSection.prototype.processRoll = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "character/gambler/" +
                    this.props.character.id +
                    "/slot-machine",
            )
            .doAjaxCall(
                "post",
                function (response) {
                    _this.setState({
                        roll: response.data.rolls,
                        roll_message: response.data.message,
                        spinning: false,
                    });
                },
                function (error) {
                    _this.setState({ spinning: false });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    };
    GamblingSection.prototype.renderIcons = function (index) {
        var icon = this.state.icons[index];
        return React.createElement(
            "div",
            { className: "text-center mb-10" },
            React.createElement("i", {
                className: icon.icon + " text-7xl",
                style: { color: icon.color },
            }),
            React.createElement("p", { className: "text-lg mt-2" }, icon.title),
        );
    };
    GamblingSection.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        if (this.state.spinning && this.state.spinningIndexes.length > 0) {
            return React.createElement(SpinSection, {
                icons: this.state.icons,
                is_small: this.props.is_small,
                spinning_indexes: this.state.spinningIndexes,
                spin_action: this.spin.bind(this),
            });
        }
        return React.createElement(
            "div",
            {
                className: clsx("max-w-[450px] m-auto lg:mr-auto", {
                    "ml-[150px]": !this.props.is_small,
                }),
            },
            React.createElement(StationarySpinSection, {
                roll: this.state.roll,
                icons: this.state.icons,
            }),
            React.createElement(SuccessMessage, {
                success_message: this.state.roll_message,
            }),
            React.createElement(ErrorMessage, {
                error_message: this.state.error_message,
            }),
            React.createElement(
                "div",
                { className: "text-center" },
                React.createElement(
                    "div",
                    { className: "flex justify-center mb-2" },
                    React.createElement(SuccessButton, {
                        button_label: "Spin",
                        on_click: this.spin.bind(this),
                        disabled: !this.props.character.can_spin,
                    }),
                    React.createElement(DangerButton, {
                        button_label: "close",
                        on_click: this.props.close_gambling_section,
                        additional_css: "ml-2",
                    }),
                ),
                React.createElement(
                    "p",
                    { className: "text-sm mb-4" },
                    "Cost Per Spin: 1,000,000 Gold",
                ),
                React.createElement(
                    "p",
                    null,
                    React.createElement(
                        "a",
                        {
                            href: "/information/slots",
                            target: "_blank",
                            className: "ml-2",
                        },
                        "Help ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                ),
                this.state.timeoutFor !== 0
                    ? React.createElement(
                          "div",
                          { className: "ml-auto mr-auto" },
                          React.createElement(TimerProgressBar, {
                              time_remaining: this.state.timeoutFor,
                              time_out_label: "Spin TimeOut",
                          }),
                      )
                    : null,
            ),
        );
    };
    return GamblingSection;
})(React.Component);
export default GamblingSection;
//# sourceMappingURL=gambling-section.js.map
