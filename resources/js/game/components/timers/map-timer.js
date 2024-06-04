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
import TimerProgressBar from "../ui/progress-bars/timer-progress-bar";
var MapTimer = (function (_super) {
    __extends(MapTimer, _super);
    function MapTimer(props) {
        return _super.call(this, props) || this;
    }
    MapTimer.prototype.render = function () {
        if (
            this.props.automation_time_out !== 0 &&
            this.props.time_left !== 0
        ) {
            return React.createElement(
                Fragment,
                null,
                React.createElement(
                    "div",
                    { className: "grid grid-cols-2 gap-2 mb-4" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement(TimerProgressBar, {
                            time_remaining: this.props.time_left,
                            time_out_label: "Movement Timeout",
                        }),
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(TimerProgressBar, {
                            time_remaining: this.props.automation_time_out,
                            time_out_label: "Exploration",
                        }),
                    ),
                ),
                React.createElement(TimerProgressBar, {
                    time_remaining: this.props.celestial_time_out,
                    time_out_label: "Celestial Timeout",
                }),
            );
        }
        if (this.props.celestial_time_out !== 0 && this.props.time_left !== 0) {
            return React.createElement(
                Fragment,
                null,
                React.createElement(
                    "div",
                    { className: "grid grid-cols-2 gap-2 mb-4" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement(TimerProgressBar, {
                            time_remaining: this.props.time_left,
                            time_out_label: "Movement Timeout",
                        }),
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(TimerProgressBar, {
                            time_remaining: this.props.celestial_time_out,
                            time_out_label: "Celestial Timeout",
                        }),
                    ),
                ),
            );
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(TimerProgressBar, {
                time_remaining: this.props.automation_time_out,
                time_out_label: "Exploration",
            }),
            React.createElement(TimerProgressBar, {
                time_remaining: this.props.time_left,
                time_out_label: "Movement",
            }),
            React.createElement(TimerProgressBar, {
                time_remaining: this.props.celestial_time_out,
                time_out_label: "Celestial Timeout",
            }),
        );
    };
    return MapTimer;
})(React.Component);
export default MapTimer;
//# sourceMappingURL=map-timer.js.map
