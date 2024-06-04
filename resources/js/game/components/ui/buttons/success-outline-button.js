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
var SuccessOutlineButton = (function (_super) {
    __extends(SuccessOutlineButton, _super);
    function SuccessOutlineButton(props) {
        return _super.call(this, props) || this;
    }
    SuccessOutlineButton.prototype.render = function () {
        return React.createElement(
            "button",
            {
                type: "button",
                className:
                    "py-2 px-3 text-xs border-green-600 border-2 font-medium text-center text-gray-900 " +
                    "dark:text-white hover:text-gray-200 dark:hover:text-gray-300 hover:bg-green-700 rounded-sm " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-green-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 disabled:bg-green-600 disabled:bg-opacity-75 dark:disabled:bg-opacity-50 " +
                    "dark:disabled:bg-green-500 disabled:text-white " +
                    this.props.additional_css,
                onClick: this.props.on_click,
                disabled: this.props.disabled,
            },
            this.props.button_label,
        );
    };
    return SuccessOutlineButton;
})(React.Component);
export default SuccessOutlineButton;
//# sourceMappingURL=success-outline-button.js.map
