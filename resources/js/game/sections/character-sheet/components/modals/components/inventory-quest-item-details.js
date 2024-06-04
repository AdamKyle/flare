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
var InventoryQuestItemDetails = (function (_super) {
    __extends(InventoryQuestItemDetails, _super);
    function InventoryQuestItemDetails(props) {
        return _super.call(this, props) || this;
    }
    InventoryQuestItemDetails.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "max-h-[400px] overflow-y-auto" },
            React.createElement("div", {
                className: "mb-4 mt-4 text-sky-700 dark:text-sky-300",
                dangerouslySetInnerHTML: {
                    __html: this.props.item.description,
                },
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-3 gap-3 mb-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-300" },
                        "Skill Modifiers",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Effects Skill"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.skill_name !== null
                                ? this.props.item.skill_name
                                : "N/A",
                        ),
                        React.createElement("dt", null, "Skill Bonus"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.skill_bonus * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Skill XP Bonus"),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.item.skill_training_bonus * 100
                            ).toFixed(2),
                            "%",
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-300" },
                        "Devouring Chance",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Devouring Light"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.devouring_light * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Devouring Darkness"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.devouring_darkness * 100).toFixed(
                                2,
                            ),
                            "%",
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-300" },
                        "XP Bonus",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Xp Bonus"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.xp_bonus * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Ignores Caps?"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.ignores_caps ? "Yes" : "No",
                        ),
                    ),
                ),
            ),
        );
    };
    return InventoryQuestItemDetails;
})(React.Component);
export default InventoryQuestItemDetails;
//# sourceMappingURL=inventory-quest-item-details.js.map
