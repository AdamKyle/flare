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
import React, { Fragment } from "react";
var GemDetails = (function (_super) {
    __extends(GemDetails, _super);
    function GemDetails(props) {
        return _super.call(this, props) || this;
    }
    GemDetails.prototype.renderDetails = function (gem) {
        return React.createElement(
            "div",
            { className: "grid md:grid-cols-2 gap-2" },
            React.createElement(
                "div",
                null,
                React.createElement(
                    "dl",
                    null,
                    React.createElement("dt", null, "Tier"),
                    React.createElement("dd", null, gem.tier),
                    React.createElement(
                        "dt",
                        null,
                        gem.primary_atonement_name + " Atonement: ",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        (gem.primary_atonement_amount * 100).toFixed(0),
                        "%",
                    ),
                    React.createElement(
                        "dt",
                        null,
                        gem.secondary_atonement_name + " Atonement: ",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        (gem.secondary_atonement_amount * 100).toFixed(0),
                        "%",
                    ),
                    React.createElement(
                        "dt",
                        null,
                        gem.tertiary_atonement_name + " Atonement: ",
                    ),
                    React.createElement(
                        "dd",
                        null,
                        (gem.tertiary_atonement_amount * 100).toFixed(0),
                        "%",
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "div",
                null,
                React.createElement(
                    "dl",
                    null,
                    React.createElement("dt", null, "Atoned To"),
                    React.createElement("dd", null, gem.element_atoned_to),
                    React.createElement("dt", null, "Atoned Amount"),
                    React.createElement(
                        "dd",
                        null,
                        (gem.element_atoned_to_amount * 100).toFixed(0),
                        "%",
                    ),
                    React.createElement("dt", null, "Strong Against"),
                    React.createElement("dd", null, gem.strong_against),
                    React.createElement("dt", null, "Weak Against"),
                    React.createElement("dd", null, gem.weak_against),
                ),
            ),
        );
    };
    GemDetails.prototype.getGem = function () {
        return this.props.gem;
    };
    GemDetails.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                null,
                React.createElement(
                    "p",
                    { className: "mb-2 mt-2" },
                    "Atonement refers to the elemental resistance against Raid and PVP battles. It can also refer to the amount of elemental damage you do per hit, which is a % of your damage.",
                ),
                React.createElement(
                    "p",
                    { className: "mb-2 mt-2" },
                    "When determining the elemental damage %, we take the highest elemental atonement %. Checkout",
                    " ",
                    React.createElement("a", { href: "#" }, "Gem Crafting"),
                    " for more info.",
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
            }),
            this.renderDetails(this.getGem()),
        );
    };
    return GemDetails;
})(React.Component);
export default GemDetails;
//# sourceMappingURL=gem-details.js.map
