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
var SellInformation = (function (_super) {
    __extends(SellInformation, _super);
    function SellInformation(props) {
        return _super.call(this, props) || this;
    }
    SellInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                null,
                "Are you sure? You are about to sell all items in your inventory (this does not effect Alchemy, Quest items, Sets, Gems or Equipped items). This action cannot be undone. Also, trinkets cannot be sold to the shop. They can be listed to the market or destroyed.",
            ),
            React.createElement(
                "p",
                { className: "mt-2" },
                React.createElement("strong", null, "Note"),
                ": The amount of gold you will get back for items that are enchanted or crafted over the price of two billion gold will never be sold for",
                " ",
                React.createElement("strong", null, "more than"),
                " two billion gold. Ie, a 36 billion gold item will only sell for two billion gold before taxes.",
            ),
            React.createElement(
                "p",
                { className: "mt-2" },
                "It is highly recommended you use the market place to sell anything beyond shop gear to make your money back.",
            ),
        );
    };
    return SellInformation;
})(React.Component);
export default SellInformation;
//# sourceMappingURL=sell-information.js.map
