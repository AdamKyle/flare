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
var PurpleButton = (function (_super) {
    __extends(PurpleButton, _super);
    function PurpleButton(props) {
        return _super.call(this, props) || this;
    }
    PurpleButton.prototype.render = function () {
        return React.createElement(
            "button",
            {
                className:
                    "hover:bg-fuchsia-600 hover:drop-shadow-md dark:text-white hover:text-gray-300 " +
                    "bg-fuchsia-500 dark:bg-fuchsia-600 text-white dark:hover:bg-fuchsia-600 dark:hover:text-white font-semibold " +
                    "py-2 px-4 rounded-sm drop-shadow-sm disabled:bg-fuchsia-400 dark:disabled:bg-fuchsia-400 " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-fuchsia-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 " +
                    this.props.additional_css,
                onClick: this.props.on_click,
                disabled: this.props.disabled,
            },
            this.props.button_label,
        );
    };
    return PurpleButton;
})(React.Component);
export default PurpleButton;
//# sourceMappingURL=purple-button.js.map
