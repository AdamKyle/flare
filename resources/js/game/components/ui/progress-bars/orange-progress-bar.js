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
var OrangeProgressBar = (function (_super) {
    __extends(OrangeProgressBar, _super);
    function OrangeProgressBar(props) {
        return _super.call(this, props) || this;
    }
    OrangeProgressBar.prototype.render = function () {
        return React.createElement(
            "div",
            {
                className: clsx({
                    "relative top-[24px]": this.props.push_down,
                }),
            },
            React.createElement(
                "div",
                { className: "flex justify-between mb-1" },
                React.createElement(
                    "span",
                    {
                        className:
                            "font-medium text-orange-700 dark:text-white text-xs " +
                            this.props.text_override_class,
                    },
                    this.props.primary_label,
                ),
                React.createElement(
                    "span",
                    {
                        className:
                            "text-xs font-medium text-orange-700 dark:text-white " +
                            this.props.text_override_class,
                    },
                    this.props.secondary_label,
                ),
            ),
            React.createElement(
                "div",
                {
                    className:
                        "w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 " +
                        this.props.height_override_class,
                },
                React.createElement("div", {
                    className:
                        "bg-orange-600 h-1.5 rounded-full " +
                        this.props.height_override_class,
                    style: { width: this.props.percentage_filled + "%" },
                }),
            ),
        );
    };
    return OrangeProgressBar;
})(React.Component);
export default OrangeProgressBar;
//# sourceMappingURL=orange-progress-bar.js.map
