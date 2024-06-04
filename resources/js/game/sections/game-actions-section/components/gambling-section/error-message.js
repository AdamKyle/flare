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
var ErrorMessage = (function (_super) {
    __extends(ErrorMessage, _super);
    function ErrorMessage(props) {
        return _super.call(this, props) || this;
    }
    ErrorMessage.prototype.render = function () {
        if (this.props.error_message === null) {
            return;
        }
        return React.createElement(
            "div",
            {
                className:
                    "mb-4 italic text-center text-red-700 dark:text-red-500 text-lg",
            },
            this.props.error_message,
        );
    };
    return ErrorMessage;
})(React.Component);
export default ErrorMessage;
//# sourceMappingURL=error-message.js.map
