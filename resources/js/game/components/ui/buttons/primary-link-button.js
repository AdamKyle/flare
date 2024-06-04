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
var PrimaryLinkButton = (function (_super) {
    __extends(PrimaryLinkButton, _super);
    function PrimaryLinkButton(props) {
        return _super.call(this, props) || this;
    }
    PrimaryLinkButton.prototype.render = function () {
        return React.createElement(
            "button",
            {
                className:
                    "hover:text-blue-500 text-blue-700 dark:text-blue-500 dark:hover:text-blue-400 " +
                    "disabled:text-blue-400 dark:disabled:bg-blue-400 disabled:line-through " +
                    "focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 dark:focus-visible:ring-white " +
                    "focus-visible:ring-opacity-75 " +
                    this.props.additional_css,
                onClick: this.props.on_click,
                disabled: this.props.disabled,
            },
            this.props.button_label,
        );
    };
    return PrimaryLinkButton;
})(React.Component);
export default PrimaryLinkButton;
//# sourceMappingURL=primary-link-button.js.map
