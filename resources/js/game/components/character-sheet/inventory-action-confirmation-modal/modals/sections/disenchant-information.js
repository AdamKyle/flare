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
var DisenchantInformation = (function (_super) {
    __extends(DisenchantInformation, _super);
    function DisenchantInformation(props) {
        return _super.call(this, props) || this;
    }
    DisenchantInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                null,
                "Are you sure you want to do this? This action will disenchant all items in your inventory. You cannot undo this action.",
            ),
            React.createElement(
                "p",
                { className: "mt-2" },
                "When you disenchant items you will get some",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/currencies", target: "_blank" },
                    "Gold Dust ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                " ",
                "and experience towards",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/disenchanting", target: "_blank" },
                    "Disenchanting",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                " ",
                "and half XP towards",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/enchanting", target: "_blank" },
                    "Enchanting ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
                ".",
            ),
            React.createElement(
                "p",
                { className: "mt-2" },
                "Tip for crafters/enchanters: Equip a set that's full enchanting when doing your mass disenchanting, because the XP you get, while only half, can be boosted. For new players, you should be crafting and enchanting and then disenchanting or selling your equipment on the market, if it is not viable for you.",
            ),
        );
    };
    return DisenchantInformation;
})(React.Component);
export default DisenchantInformation;
//# sourceMappingURL=disenchant-information.js.map
