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
var SuccessMessage = (function (_super) {
    __extends(SuccessMessage, _super);
    function SuccessMessage(props) {
        return _super.call(this, props) || this;
    }
    SuccessMessage.prototype.render = function () {
        if (this.props.success_message === null) {
            return;
        }
        return React.createElement(
            "div",
            {
                className:
                    "mb-4 italic text-center text-green-700 dark:text-green-500 text-lg",
            },
            this.props.success_message,
        );
    };
    return SuccessMessage;
})(React.Component);
export default SuccessMessage;
//# sourceMappingURL=success-message.js.map
