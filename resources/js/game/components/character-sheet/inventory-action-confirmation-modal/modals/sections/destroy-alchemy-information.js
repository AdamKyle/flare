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
var DestroyAlchemyInformation = (function (_super) {
    __extends(DestroyAlchemyInformation, _super);
    function DestroyAlchemyInformation(props) {
        return _super.call(this, props) || this;
    }
    DestroyAlchemyInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                null,
                "Are you sure you want to do this? This action will destroy all (Alchemy) items in your inventory?",
                React.createElement(
                    "strong",
                    null,
                    "You cannot undo this action",
                ),
                ".",
            ),
        );
    };
    return DestroyAlchemyInformation;
})(React.Component);
export default DestroyAlchemyInformation;
//# sourceMappingURL=destroy-alchemy-information.js.map
