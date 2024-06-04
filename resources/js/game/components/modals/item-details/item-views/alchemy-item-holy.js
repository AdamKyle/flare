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
import ItemHolyEffects from "../values/item-holy-effects";
import { serviceContainer } from "../../../../lib/containers/core-container";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";
var AlchemyItemHoly = (function (_super) {
    __extends(AlchemyItemHoly, _super);
    function AlchemyItemHoly(props) {
        var _this = _super.call(this, props) || this;
        _this.itemHolyEffects = serviceContainer().fetch(ItemHolyEffects);
        return _this;
    }
    AlchemyItemHoly.prototype.render = function () {
        if (this.props.item.holy_level === null) {
            return React.createElement(
                DangerAlert,
                { additional_css: "my-4" },
                React.createElement("strong", null, "Error"),
                ": Holy Oil does not seem to have a level associated with it.",
            );
        }
        var effects = this.itemHolyEffects.determineItemHolyEffects(
            this.props.item.holy_level,
        );
        return React.createElement(
            "div",
            { className: "mr-auto ml-auto w-3/5" },
            React.createElement(
                "p",
                { className: "mt-4 mb-4" },
                this.props.item.description,
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "p",
                { className: "my-4 text-sky-700 dark:text-sky-600" },
                "These items can only be used at the Purgatory Smiths Workbench in Purgatory on items you wish to enhance. Each oil will stack with the other. The amount of oils one can apply to their weapons and armour is dependant on the craft level of that item. Set items, always have the max holy stacks that can applied.",
            ),
            React.createElement(
                "p",
                { className: "my-4 text-sky-700 dark:text-sky-600" },
                "As you can see below, the oil has a Holy Level, the higher the level (max 5) the better the stats applied to the item.",
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Holy Level"),
                React.createElement("dt", null, this.props.item.holy_level),
                React.createElement("dt", null, "Stat Increase Per Item used"),
                React.createElement("dd", null, effects.stat_increase, "%"),
                React.createElement(
                    "dt",
                    null,
                    "Devouring Resistance Increase Per Item used",
                ),
                React.createElement(
                    "dd",
                    null,
                    effects.devouring_adjustment,
                    "%",
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "p",
                { className: "my-4" },
                "Read more about Holy Items in the",
                " ",
                React.createElement(
                    "a",
                    { href: "/information/holy-items", target: "_blank" },
                    "Help Docs ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return AlchemyItemHoly;
})(React.Component);
export default AlchemyItemHoly;
//# sourceMappingURL=alchemy-item-holy.js.map
