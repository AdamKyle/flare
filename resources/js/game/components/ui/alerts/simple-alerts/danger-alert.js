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
var DangerAlert = (function (_super) {
    __extends(DangerAlert, _super);
    function DangerAlert(props) {
        return _super.call(this, props) || this;
    }
    DangerAlert.prototype.render = function () {
        return React.createElement(
            "div",
            {
                className:
                    "border-l-2 border-l-red-500 bg-red-50 dark:bg-red-600/[.15] p-4 pl-[10px] " +
                    this.props.additional_css,
            },
            React.createElement(
                "div",
                { className: "flex justify-between" },
                React.createElement(
                    "span",
                    { className: "self-center text-red-500 dark:text-red-400" },
                    this.props.children,
                ),
                typeof this.props.close_alert !== "undefined"
                    ? React.createElement(
                          "strong",
                          {
                              className:
                                  "text-xl align-center cursor-pointer text-red-500 dark:text-red-400",
                              onClick: this.props.close_alert,
                          },
                          "\u00D7",
                      )
                    : null,
            ),
        );
    };
    return DangerAlert;
})(React.Component);
export default DangerAlert;
//# sourceMappingURL=danger-alert.js.map
