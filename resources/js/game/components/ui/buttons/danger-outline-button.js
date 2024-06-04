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
var DangerOutlineButton = (function (_super) {
    __extends(DangerOutlineButton, _super);
    function DangerOutlineButton(props) {
        return _super.call(this, props) || this;
    }
    DangerOutlineButton.prototype.render = function () {
        return React.createElement(
            "button",
            {
                type: "button",
                className:
                    "py-2 px-3 text-xs border-red-600 border-2 font-medium text-center text-gray-900 " +
                    "dark:text-white hover:text-gray-200 dark:hover:text-gray-300 hover:bg-red-700 rounded-sm " +
                    "focus:ring-4 focus:ring-red-300 dark:hover:bg-red-800 dark:focus:ring-red-800 " +
                    "disabled:bg-red-600 disabled:bg-opacity-75 dark:disabled:bg-opacity-50 " +
                    "dark:disabled:bg-red-500 disabled:text-white " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-red-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 " +
                    this.props.additional_css,
                onClick: this.props.on_click,
                disabled: this.props.disabled,
            },
            this.props.button_label,
        );
    };
    return DangerOutlineButton;
})(React.Component);
export default DangerOutlineButton;
//# sourceMappingURL=danger-outline-button.js.map
