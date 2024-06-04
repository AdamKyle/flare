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
var SellSelectedInformation = (function (_super) {
    __extends(SellSelectedInformation, _super);
    function SellSelectedInformation(props) {
        return _super.call(this, props) || this;
    }
    SellSelectedInformation.prototype.renderSelectedItemNames = function () {
        return this.props.item_names.map(function (name) {
            return React.createElement("li", null, name);
        });
    };
    SellSelectedInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                null,
                "Are you sure? You are about to sell the selected items to the shop. Should an items value go beyond the shop: 2 Billion Gold, then the item will only be sold for 2 Billion gold. It is suggested players use the market to sell more valuable items.",
                React.createElement(
                    "strong",
                    null,
                    "This action cannot be undone.",
                ),
            ),
            React.createElement(
                "ul",
                { className: "my-3 pl-4 list-disc ml-4" },
                this.renderSelectedItemNames(),
            ),
        );
    };
    return SellSelectedInformation;
})(React.Component);
export default SellSelectedInformation;
//# sourceMappingURL=sell-selected-information.js.map
