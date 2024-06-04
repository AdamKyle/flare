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
import clsx from "clsx";
var HealthMeters = (function (_super) {
    __extends(HealthMeters, _super);
    function HealthMeters(props) {
        return _super.call(this, props) || this;
    }
    HealthMeters.prototype.abbreviateNumber = function (stat) {
        if (typeof stat === "undefined") {
            return 0;
        }
        var statNumber = stat;
        var symbol = ["", "k", "M", "B", "T", "Quad.", "Qunit."];
        var tier = (Math.log10(Math.abs(statNumber)) / 3) | 0;
        if (tier == 0) return statNumber;
        var suffix = symbol[tier];
        var scale = Math.pow(10, tier * 3);
        var scaled = statNumber / scale;
        return scaled.toFixed(0) + suffix;
    };
    HealthMeters.prototype.healthPercent = function () {
        if (
            typeof this.props.current_health === "undefined" ||
            typeof this.props.max_health === "undefined"
        ) {
            return 0;
        }
        var percentage = this.props.current_health / this.props.max_health;
        if (percentage > 1.0) {
            return 1.0;
        }
        return percentage;
    };
    HealthMeters.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "mb-2" },
            React.createElement(
                "div",
                { className: "flex justify-between mb-1" },
                React.createElement(
                    "span",
                    {
                        className: clsx(
                            "font-medium dark:text-white text-xs",
                            {
                                "text-red-600 dark:text-red-400":
                                    this.props.is_enemy,
                            },
                            {
                                "text-green-700 dark:text-green-500":
                                    !this.props.is_enemy,
                            },
                        ),
                    },
                    this.props.name,
                ),
                React.createElement(
                    "span",
                    {
                        className: clsx(
                            "font-medium dark:text-white text-xs",
                            {
                                "text-red-600 dark:text-red-400":
                                    this.props.is_enemy,
                            },
                            {
                                "text-green-700 dark:text-green-500":
                                    !this.props.is_enemy,
                            },
                        ),
                    },
                    this.abbreviateNumber(this.props.current_health),
                    " /",
                    " ",
                    this.abbreviateNumber(this.props.max_health),
                ),
            ),
            React.createElement(
                "div",
                {
                    className:
                        "w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700",
                },
                React.createElement("div", {
                    className: clsx(
                        "h-1.5 rounded-full",
                        {
                            "bg-red-600": this.props.is_enemy,
                        },
                        {
                            "bg-green-600": !this.props.is_enemy,
                        },
                    ),
                    style: { width: this.healthPercent() * 100 + "%" },
                }),
            ),
        );
    };
    return HealthMeters;
})(React.Component);
export default HealthMeters;
//# sourceMappingURL=health-meters.js.map
