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
var ManualProgressBar = (function (_super) {
    __extends(ManualProgressBar, _super);
    function ManualProgressBar(props) {
        return _super.call(this, props) || this;
    }
    ManualProgressBar.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "flex justify-between mb-1" },
                React.createElement(
                    "span",
                    {
                        className:
                            "text-base font-medium text-gray-800 dark:text-white",
                    },
                    this.props.label,
                    this.props.show_loading_icon
                        ? React.createElement("i", {
                              className: "ml-2 fas fa-spinner fa-pulse",
                          })
                        : null,
                ),
                React.createElement(
                    "span",
                    {
                        className:
                            "text-sm font-medium text-gray-800 dark:text-white",
                    },
                    this.props.secondary_label,
                ),
            ),
            React.createElement(
                "div",
                {
                    className:
                        "w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700",
                },
                React.createElement("div", {
                    className:
                        "h-2.5 rounded-full bg-blue-600 dark:bg-blue-500",
                    style: {
                        width: this.props.percentage_left * 100 + "%",
                    },
                }),
            ),
        );
    };
    return ManualProgressBar;
})(React.Component);
export default ManualProgressBar;
//# sourceMappingURL=manual-progress-bar.js.map
