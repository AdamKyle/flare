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
var DangerLinkButton = (function (_super) {
    __extends(DangerLinkButton, _super);
    function DangerLinkButton(props) {
        return _super.call(this, props) || this;
    }
    DangerLinkButton.prototype.render = function () {
        return React.createElement(
            "button",
            {
                className:
                    "hover:text-red-500 text-red-700 dark:text-red-500 dark:hover:text-red-400 " +
                    "disabled:text-red-400 dark:disabled:bg-red-400 disabled:line-through " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-red-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 " +
                    this.props.additional_css,
                onClick: this.props.on_click,
                disabled: this.props.disabled,
            },
            this.props.button_label,
        );
    };
    return DangerLinkButton;
})(React.Component);
export default DangerLinkButton;
//# sourceMappingURL=danger-link-button.js.map
