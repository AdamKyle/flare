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
import clsx from "clsx";
var TimerProgressBar = (function (_super) {
    __extends(TimerProgressBar, _super);
    function TimerProgressBar(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            time_left: 0,
            percentage_left: 0,
            label: "seconds",
            time_left_label: 0,
            initial_time: 0,
        };
        _this.interval = null;
        return _this;
    }
    TimerProgressBar.prototype.componentDidMount = function () {
        this.initializeTimer();
    };
    TimerProgressBar.prototype.componentDidUpdate = function (
        prevProps,
        prevState,
        snapshot,
    ) {
        if (prevProps.time_remaining != this.props.time_remaining) {
            clearInterval(this.interval);
            this.initializeTimer();
        }
        if (this.state.time_left < 0) {
            clearInterval(this.interval);
            this.setState({
                time_left: 0,
            });
        }
    };
    TimerProgressBar.prototype.initializeTimer = function () {
        var _this = this;
        this.setState(
            {
                time_left: this.props.time_remaining,
                percentage_left: this.props.time_remaining > 0 ? 1.0 : 0.0,
                label: this.getLabel(),
                time_left_label: this.getTimeLabel(this.props.time_remaining),
                initial_time: this.props.time_remaining,
            },
            function () {
                if (
                    _this.props.time_remaining > 0 &&
                    _this.state.time_left > 0
                ) {
                    _this.interval = setInterval(function () {
                        var newTime = _this.state.time_left - 1;
                        if (newTime <= 0) {
                            _this.setState({
                                time_left: 0,
                                percentage_left: 0,
                                label: "seconds",
                                time_left_label: 0,
                            });
                            if (
                                typeof _this.props.update_time_remaining !==
                                "undefined"
                            ) {
                                _this.props.update_time_remaining(0);
                            }
                            clearInterval(_this.interval);
                        } else {
                            _this.setState({
                                time_left: newTime,
                                percentage_left:
                                    newTime / _this.props.time_remaining,
                                label: _this.getLabel(newTime),
                                time_left_label: _this.getTimeLabel(newTime),
                            });
                        }
                    }, 1000);
                } else {
                    clearInterval(_this.interval);
                }
            },
        );
    };
    TimerProgressBar.prototype.getLabel = function (newTime) {
        var label = "seconds";
        var time = this.props.time_remaining;
        if (newTime) {
            time = newTime;
        }
        if (time / 3600 >= 1) {
            label = "hour(s)";
        } else if (time / 60 >= 1) {
            label = "minute(s)";
        }
        return label;
    };
    TimerProgressBar.prototype.getTimeLabel = function (newTime) {
        var timeLeftLabel = newTime;
        if (newTime / 3600 >= 1) {
            timeLeftLabel = parseInt((newTime / 3600).toFixed(0));
        } else if (newTime / 60 >= 1) {
            timeLeftLabel = parseInt((newTime / 60).toFixed(0));
        }
        return timeLeftLabel;
    };
    TimerProgressBar.prototype.render = function () {
        if (
            (this.state.percentage_left <= 0 && this.state.time_left <= 0) ||
            this.props.time_remaining === 0
        ) {
            return null;
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                {
                    className: clsx(
                        {
                            "flex justify-between mb-1 ":
                                !this.props.useSmallTimer,
                        },
                        {
                            "flex md:justify-between mb-1":
                                this.props.useSmallTimer,
                        },
                        typeof this.props.additional_css !== "undefined"
                            ? this.props.additional_css
                            : "",
                    ),
                },
                React.createElement(
                    "span",
                    {
                        className:
                            "text-base font-medium text-gray-800 dark:text-white mr-4 md:mr-0",
                    },
                    this.props.time_out_label,
                ),
                React.createElement(
                    "span",
                    {
                        className:
                            "text-sm font-medium text-gray-800 dark:text-white mt-[3px]",
                    },
                    this.state.time_left_label,
                    " ",
                    this.state.label,
                    " left",
                ),
            ),
            React.createElement(
                "div",
                {
                    className: clsx(
                        "bg-gray-200 rounded-full h-1.5 dark:bg-gray-700",
                        { "w-full": !this.props.useSmallTimer },
                        { "w-1/2": this.props.useSmallTimer },
                    ),
                },
                React.createElement("div", {
                    className:
                        "h-1.5 rounded-full " +
                        (this.state.percentage_left >= 0.75
                            ? "bg-fuchsia-600 dark:bg-fuchsia-700"
                            : this.state.percentage_left < 0.75 &&
                                this.state.percentage_left >= 0.5
                              ? "bg-fuchsia-500 dark:bg-fuchsia-600"
                              : this.state.percentage_left >= 0.25 &&
                                  this.state.percentage_left < 0.5
                                ? "bg-fuchsia-400 dark:bg-fuchsia-500"
                                : this.state.percentage_left >= 0.0 &&
                                    this.state.percentage_left < 0.25
                                  ? "bg-fuchsia-300 dark:bg-fuchsia-400"
                                  : ""),
                    style: {
                        width: this.state.percentage_left * 100 + "%",
                    },
                }),
            ),
        );
    };
    return TimerProgressBar;
})(React.Component);
export default TimerProgressBar;
//# sourceMappingURL=timer-progress-bar.js.map
