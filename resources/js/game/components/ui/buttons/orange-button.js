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
var OrangeButton = (function (_super) {
    __extends(OrangeButton, _super);
    function OrangeButton(props) {
        return _super.call(this, props) || this;
    }
    OrangeButton.prototype.render = function () {
        return React.createElement(
            "button",
            {
                className:
                    "hover:bg-orange-600 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-orange-500 " +
                    "dark:bg-orange-600 text-white dark:hover:bg-orange-600 dark:hover:text-white font-semibold py-2 px-4 " +
                    "rounded-sm drop-shadow-sm disabled:bg-orange-400 dark:disabled:bg-orange-400 " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 " +
                    this.props.additional_css,
                onClick: this.props.on_click,
                disabled: this.props.disabled,
            },
            this.props.button_label,
        );
    };
    return OrangeButton;
})(React.Component);
export default OrangeButton;
//# sourceMappingURL=orange-button.js.map
