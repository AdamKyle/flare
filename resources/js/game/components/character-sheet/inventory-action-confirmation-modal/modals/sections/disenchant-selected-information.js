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
var DisenchantSelectedInformation = (function (_super) {
    __extends(DisenchantSelectedInformation, _super);
    function DisenchantSelectedInformation(props) {
        return _super.call(this, props) || this;
    }
    DisenchantSelectedInformation.prototype.renderSelectedItemNames =
        function () {
            return this.props.item_names.map(function (name) {
                return React.createElement("li", null, name);
            });
        };
    DisenchantSelectedInformation.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                null,
                "Are you sure you want to do this? This action will disenchant all selected items below.",
                " ",
                React.createElement(
                    "strong",
                    null,
                    "You cannot undo this action",
                ),
                ".",
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "span",
                { className: "mb-3" },
                React.createElement("strong", null, "Items to Disenchant"),
            ),
            React.createElement(
                "ul",
                { className: "my-3 pl-4 list-disc ml-4" },
                this.renderSelectedItemNames(),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
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
    return DisenchantSelectedInformation;
})(React.Component);
export default DisenchantSelectedInformation;
//# sourceMappingURL=disenchant-selected-information.js.map
