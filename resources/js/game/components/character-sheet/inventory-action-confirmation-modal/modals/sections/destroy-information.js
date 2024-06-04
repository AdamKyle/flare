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
var DestroyInformation = (function (_super) {
    __extends(DestroyInformation, _super);
    function DestroyInformation(props) {
        return _super.call(this, props) || this;
    }
    DestroyInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                null,
                "Are you sure? You will destroy all items in your inventory. Quest Items, Gems and Alchemy Items will be untouched as will anything in Sets or currently equipped.",
                " ",
                React.createElement(
                    "strong",
                    null,
                    "You cannot undo this action",
                ),
                ".",
            ),
        );
    };
    return DestroyInformation;
})(React.Component);
export default DestroyInformation;
//# sourceMappingURL=destroy-information.js.map
