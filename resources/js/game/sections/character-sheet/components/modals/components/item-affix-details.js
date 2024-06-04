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
import Dialogue from "../../../../../components/ui/dialogue/dialogue";
var ItemAffixDetails = (function (_super) {
    __extends(ItemAffixDetails, _super);
    function ItemAffixDetails(props) {
        return _super.call(this, props) || this;
    }
    ItemAffixDetails.prototype.render = function () {
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: this.props.affix.name,
                large_modal: true,
            },
            React.createElement(
                "div",
                { className: "max-h-[350px] overflow-y-auto" },
                React.createElement("div", {
                    className: "mb-4 mt-4 text-sky-700 dark:text-sky-500",
                    dangerouslySetInnerHTML: {
                        __html: this.props.affix.description,
                    },
                }),
                React.createElement(
                    "div",
                    { className: "grid md:grid-cols-2 gap-3" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Stats",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Str Modifier"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.str_mod * 100).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Dex Modifier"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.dex_mod * 100).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Agi Modifier"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.agi_mod * 100).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Chr Modifier"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.chr_mod * 100).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Dur Modifier"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.dur_mod * 100).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Int Modifier"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.int_mod * 100).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Focus Modifier"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.focus_mod * 100).toFixed(2),
                                "%",
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Skill Modifiers",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Skill Name"),
                            React.createElement(
                                "dd",
                                null,
                                this.props.affix.skill_name !== null
                                    ? this.props.affix.skill_name
                                    : "N/A",
                            ),
                            React.createElement("dt", null, "Skill XP Bonus"),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.skill_training_bonus * 100
                                ).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Skill Bonus"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.skill_bonus * 100).toFixed(2),
                                "%",
                            ),
                        ),
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Damage/AC/Healing Modifiers",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement(
                                "dt",
                                null,
                                "Base Attack Modifier",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.base_damage_mod * 100
                                ).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Base AC Modifier"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.base_ac_mod * 100).toFixed(2),
                                "%",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Base Healing Modifier",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.base_healing_mod * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    { className: "grid md:grid-cols-2 gap-3" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Damage",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Damage:"),
                            React.createElement(
                                "dd",
                                null,
                                this.props.affix.damage_amount * 100,
                                "%",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Is Damage Irresistible?:",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                this.props.affix.irresistible_damage
                                    ? "Yes"
                                    : "No",
                            ),
                            React.createElement("dt", null, "Can Stack:"),
                            React.createElement(
                                "dd",
                                null,
                                this.props.affix.damage_can_stack
                                    ? "Yes"
                                    : "No",
                            ),
                        ),
                        React.createElement(
                            "p",
                            { className: "my-4" },
                            "Damage is a % of your total weapon damage after all modifiers.",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Stat Reduction",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Str Reduction:"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.str_reduction * 100).toFixed(
                                    2,
                                ),
                                "%",
                            ),
                            React.createElement("dt", null, "Dex Reduction:"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.dex_reduction * 100).toFixed(
                                    2,
                                ),
                                "%",
                            ),
                            React.createElement("dt", null, "Dur Reduction:"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.dur_reduction * 100).toFixed(
                                    2,
                                ),
                                "%",
                            ),
                            React.createElement("dt", null, "Int Reduction:"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.int_reduction * 100).toFixed(
                                    2,
                                ),
                                "%",
                            ),
                            React.createElement("dt", null, "Chr Reduction:"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.chr_reduction * 100).toFixed(
                                    2,
                                ),
                                "%",
                            ),
                            React.createElement("dt", null, "Agi Reduction:"),
                            React.createElement(
                                "dd",
                                null,
                                (this.props.affix.agi_reduction * 100).toFixed(
                                    2,
                                ),
                                "%",
                            ),
                            React.createElement("dt", null, "Focus Reduction:"),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.focus_reduction * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Life Stealing",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Damage:"),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.steal_life_amount * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Entrance",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Chance:"),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.entranced_chance * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Devouring Light",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement(
                                "dt",
                                null,
                                "Devouring Light Chance:",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.devouring_light * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Skill Reduction",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Skills Affected:"),
                            React.createElement(
                                "dd",
                                null,
                                "Accuracy, Dodge, Casting Accuracy and Criticality",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Skills Reduced By:",
                            ),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.skill_reduction * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "h4",
                            { className: "text-sky-600 dark:text-sky-500" },
                            "Resistance Reduction",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Reduction:"),
                            React.createElement(
                                "dd",
                                null,
                                (
                                    this.props.affix.resistance_reduction * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                    ),
                ),
            ),
        );
    };
    return ItemAffixDetails;
})(React.Component);
export default ItemAffixDetails;
//# sourceMappingURL=item-affix-details.js.map
