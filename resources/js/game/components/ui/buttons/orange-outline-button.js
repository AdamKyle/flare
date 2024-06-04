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
var OrangeOutlineButton = (function (_super) {
    __extends(OrangeOutlineButton, _super);
    function OrangeOutlineButton(props) {
        return _super.call(this, props) || this;
    }
    OrangeOutlineButton.prototype.render = function () {
        return React.createElement(
            "button",
            {
                type: "button",
                className:
                    "py-2 px-3 text-xs border-orange-500 border-2 font-medium text-center " +
                    "text-gray-900 dark:text-gray-200 hover:text-gray-200 dark:hover:text-gray-300 " +
                    "hover:bg-orange-600 rounded-sm focus:ring-4 focus:ring-orange-300 dark:hover:bg-orange-600 " +
                    "dark:focus:ring-orange-800 disabled:bg-orange-600 disabled:bg-opacity-75 dark:disabled:bg-opacity-50 " +
                    "dark:disabled:bg-orange-500 disabled:text-white " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 " +
                    this.props.additional_css,
                onClick: this.props.on_click,
                disabled: this.props.disabled,
            },
            this.props.button_label,
        );
    };
    return OrangeOutlineButton;
})(React.Component);
export default OrangeOutlineButton;
//# sourceMappingURL=orange-outline-button.js.map
