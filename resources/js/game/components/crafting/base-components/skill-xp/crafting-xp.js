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
import { formatNumber } from "../../../../lib/game/format-number";
var CraftingXp = (function (_super) {
    __extends(CraftingXp, _super);
    function CraftingXp(props) {
        return _super.call(this, props) || this;
    }
    CraftingXp.prototype.getXpPercentage = function () {
        var xpNext = this.props.skill_xp.next_level_xp;
        var currentXP = this.props.skill_xp.current_xp;
        return (currentXP / xpNext) * 100;
    };
    CraftingXp.prototype.render = function () {
        return React.createElement(
            "div",
            { className: "my-2" },
            React.createElement(
                "div",
                { className: "flex justify-between mb-1" },
                React.createElement(
                    "span",
                    {
                        className:
                            "font-medium text-orange-700 dark:text-white text-xs",
                    },
                    " ",
                    this.props.skill_xp.skill_name,
                    " Skill XP (LV:",
                    " ",
                    this.props.skill_xp.level,
                    ")",
                ),
                React.createElement(
                    "span",
                    {
                        className:
                            "text-xs font-medium text-orange-700 dark:text-white",
                    },
                    formatNumber(this.props.skill_xp.current_xp),
                    "/",
                    formatNumber(this.props.skill_xp.next_level_xp),
                ),
            ),
            React.createElement(
                "div",
                {
                    className:
                        "w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700",
                },
                React.createElement("div", {
                    className: "bg-orange-600 h-1.5 rounded-full",
                    style: { width: this.getXpPercentage() + "%" },
                }),
            ),
        );
    };
    return CraftingXp;
})(React.Component);
export default CraftingXp;
//# sourceMappingURL=crafting-xp.js.map
