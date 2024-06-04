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
var DestroySelectedInformation = (function (_super) {
    __extends(DestroySelectedInformation, _super);
    function DestroySelectedInformation(props) {
        return _super.call(this, props) || this;
    }
    DestroySelectedInformation.prototype.renderSelectedItemNames = function () {
        return this.props.item_names.map(function (name) {
            return React.createElement("li", null, name);
        });
    };
    DestroySelectedInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                null,
                "Below are a set of items you have selected to be destroyed. Are you sure you want to do this?",
                " ",
                React.createElement(
                    "strong",
                    null,
                    "You cannot undo this action",
                ),
                ".",
            ),
            React.createElement(
                "ul",
                { className: "my-3 pl-4 list-disc ml-4" },
                this.renderSelectedItemNames(),
            ),
        );
    };
    return DestroySelectedInformation;
})(React.Component);
export default DestroySelectedInformation;
//# sourceMappingURL=destroy-selected-information.js.map
