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
import BasicCard from "../../../components/ui/cards/basic-card";
var Messages = (function (_super) {
    __extends(Messages, _super);
    function Messages(props) {
        return _super.call(this, props) || this;
    }
    Messages.prototype.render = function () {
        return React.createElement(
            BasicCard,
            { additionalClasses: "mb-10" },
            React.createElement(
                "div",
                {
                    className:
                        "bg-gray-800 pt-4 p-2 lg:p-4 max-h-[800px] min-h-[200px] overflow-x-auto",
                },
                React.createElement(
                    "ul",
                    { className: "ml-5" },
                    this.props.children,
                ),
            ),
        );
    };
    return Messages;
})(React.Component);
export default Messages;
//# sourceMappingURL=messages.js.map
