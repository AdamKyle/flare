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
var LoadingProgressBar = (function (_super) {
    __extends(LoadingProgressBar, _super);
    function LoadingProgressBar(props) {
        return _super.call(this, props) || this;
    }
    LoadingProgressBar.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.props.show_label
                ? React.createElement(
                      "div",
                      { className: "flex justify-between mb-1 mt-5" },
                      React.createElement(
                          "span",
                          {
                              className:
                                  "text-base font-medium text-gray-800 dark:text-white",
                          },
                          this.props.label,
                      ),
                      React.createElement(
                          "span",
                          {
                              className:
                                  "text-sm font-medium text-gray-800 dark:text-white",
                          },
                          this.props.secondary_label,
                      ),
                  )
                : null,
            React.createElement(
                "div",
                {
                    className: clsx(
                        "w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 relative mb-5",
                        {
                            "mt-5": !this.props.show_label,
                        },
                    ),
                },
                React.createElement("div", {
                    className:
                        "h-2.5 rounded-full bg-blue-600 dark:bg-blue-500 loading-progress-bar",
                }),
            ),
        );
    };
    return LoadingProgressBar;
})(React.Component);
export default LoadingProgressBar;
//# sourceMappingURL=loading-progress-bar.js.map
