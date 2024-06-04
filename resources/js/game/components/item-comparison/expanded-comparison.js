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
import clsx from "clsx";
import { formatNumber } from "../../lib/game/format-number";
import ItemNameColorationText from "../items/item-name/item-name-coloration-text";
import { ItemType } from "../items/enums/item-type";
var coreAttributes = ["str", "dex", "dur", "int", "chr", "agi", "focus"];
var ExpandedComparison = (function (_super) {
    __extends(ExpandedComparison, _super);
    function ExpandedComparison(props) {
        return _super.call(this, props) || this;
    }
    ExpandedComparison.prototype.isValueBelowZero = function (value) {
        return value < 0;
    };
    ExpandedComparison.prototype.isValueAboveZero = function (value) {
        return value > 0;
    };
    ExpandedComparison.prototype.mapCoreAttributes = function (attributeName) {
        switch (attributeName) {
            case "str":
                return "Strength";
            case "dex":
                return "Dexterity";
            case "dur":
                return "Durability";
            case "int":
                return "Intelligence";
            case "chr":
                return "Charisma";
            case "agi":
                return "Agility";
            case "focus":
                return "Focus";
            default:
                return "ERROR";
        }
    };
    ExpandedComparison.prototype.renderCoreAttributes = function () {
        var _this = this;
        return coreAttributes.map(function (attribute) {
            return React.createElement(
                React.Fragment,
                null,
                React.createElement(
                    "dt",
                    null,
                    _this.mapCoreAttributes(attribute),
                ),
                React.createElement(
                    "dd",
                    {
                        className: clsx({
                            "text-green-700 dark:text-green-500":
                                _this.isValueAboveZero(
                                    _this.props.comparison_details[
                                        attribute + "_adjustment"
                                    ],
                                ),
                            "text-red-700 dark:text-red-500":
                                _this.isValueBelowZero(
                                    _this.props.comparison_details[
                                        attribute + "_adjustment"
                                    ],
                                ),
                            "text-gray-700 dark:text-white":
                                _this.props.comparison_details[
                                    attribute + "_adjustment"
                                ] === 0,
                        }),
                    },
                    (
                        _this.props.comparison_details[
                            attribute + "_adjustment"
                        ] * 100
                    ).toFixed(2) + "%",
                ),
            );
        });
    };
    ExpandedComparison.prototype.renderEnemyStatReductions = function () {
        var _this = this;
        return coreAttributes.map(function (attribute) {
            return React.createElement(
                React.Fragment,
                null,
                React.createElement(
                    "dt",
                    null,
                    _this.mapCoreAttributes(attribute),
                ),
                React.createElement(
                    "dd",
                    {
                        className: clsx({
                            "text-green-700 dark:text-green-500":
                                _this.isValueAboveZero(
                                    _this.props.comparison_details[
                                        attribute + "_reduction"
                                    ],
                                ),
                            "text-red-700 dark:text-red-500":
                                _this.isValueBelowZero(
                                    _this.props.comparison_details[
                                        attribute + "_reduction"
                                    ],
                                ),
                            "text-gray-700 dark:text-white":
                                _this.props.comparison_details[
                                    attribute + "_reduction"
                                ] === 0,
                        }),
                    },
                    (
                        _this.props.comparison_details[
                            attribute + "_reduction"
                        ] * 100
                    ).toFixed(2) + "%",
                ),
            );
        });
    };
    ExpandedComparison.prototype.renderAttackChange = function () {
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Attack"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison_details.damage_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBelowZero(
                            this.props.comparison_details.damage_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison_details.damage_adjustment ===
                            0,
                    }),
                },
                formatNumber(this.props.comparison_details.damage_adjustment),
            ),
            React.createElement("dt", null, "Base Damage Modifier"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison_details
                                    .base_damage_mod_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBelowZero(
                            this.props.comparison_details
                                .base_damage_mod_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison_details
                                .base_damage_mod_adjustment === 0,
                    }),
                },
                (
                    this.props.comparison_details.base_damage_mod_adjustment *
                    100
                ).toFixed(2),
                "%",
            ),
            this.renderSpellDetails(),
        );
    };
    ExpandedComparison.prototype.renderSpellDetails = function () {
        var validTypes = [ItemType.SPELL_DAMAGE, ItemType.RING];
        if (!validTypes.includes(this.props.comparison_details.type)) {
            return;
        }
        return React.createElement(
            React.Fragment,
            null,
            React.createElement("dt", null, "Spell Evasion"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison_details
                                    .spell_evasion_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBelowZero(
                            this.props.comparison_details
                                .spell_evasion_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison_details
                                .spell_evasion_adjustment === 0,
                    }),
                },
                (
                    this.props.comparison_details.spell_evasion_adjustment * 100
                ).toFixed(2),
                "%",
            ),
        );
    };
    ExpandedComparison.prototype.renderDefenceChange = function () {
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Defence"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison_details.ac_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBelowZero(
                            this.props.comparison_details.ac_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison_details.ac_adjustment === 0,
                    }),
                },
                formatNumber(this.props.comparison_details.ac_adjustment),
            ),
            React.createElement("dt", null, "Base AC Modifier"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison_details
                                    .base_ac_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBelowZero(
                            this.props.comparison_details.base_ac_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison_details.base_ac_adjustment ===
                            0,
                    }),
                },
                (
                    this.props.comparison_details.base_ac_adjustment * 100
                ).toFixed(2),
                "%",
            ),
        );
    };
    ExpandedComparison.prototype.renderAttackOrDefenceAdjustment = function () {
        var damageBased = [
            ItemType.WEAPON,
            ItemType.MACE,
            ItemType.STAVE,
            ItemType.FAN,
            ItemType.HAMMER,
            ItemType.GUN,
            ItemType.SPELL_DAMAGE,
            ItemType.SCRATCH_AWL,
            ItemType.RING,
        ];
        if (damageBased.includes(this.props.comparison_details.type)) {
            return this.renderAttackChange();
        }
        return this.renderDefenceChange();
    };
    ExpandedComparison.prototype.renderSkillsChanges = function () {
        if (this.props.comparison_details.skills.length === 0) {
            return;
        }
        if (this.props.comparison_details.skills.length > 1) {
            return React.createElement(
                "div",
                null,
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                }),
                React.createElement(
                    "strong",
                    null,
                    "Effected Skill Adjustments",
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                }),
                React.createElement(
                    "div",
                    { className: "grid md:grid-cols-2 gap-2" },
                    this.renderSkillChange(true),
                ),
            );
        }
        return React.createElement(
            "div",
            null,
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
            }),
            React.createElement("strong", null, "Effected Skill Adjustments"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                this.renderSkillChange(true),
            ),
        );
    };
    ExpandedComparison.prototype.renderSkillChange = function (showSeparator) {
        var _this = this;
        var hasShown = false;
        return this.props.comparison_details.skills.map(function (skill) {
            var element = React.createElement(
                React.Fragment,
                null,
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Skill Name"),
                        React.createElement("dd", null, skill.skill_name),
                        React.createElement("dt", null, "Skill Bonus Adj."),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-700 dark:text-green-500":
                                        _this.isValueAboveZero(
                                            skill.skill_bonus,
                                        ),
                                    "text-red-700 dark:text-red-500":
                                        _this.isValueBelowZero(
                                            skill.skill_bonus,
                                        ),
                                    "text-gray-700 dark:text-white":
                                        skill.skill_bonus === 0,
                                }),
                            },
                            (skill.skill_bonus * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Skill Xp Bonus Adj."),
                        React.createElement(
                            "dd",
                            {
                                className: clsx({
                                    "text-green-700 dark:text-green-500":
                                        _this.isValueAboveZero(
                                            skill.skill_training_bonus,
                                        ),
                                    "text-red-700 dark:text-red-500":
                                        _this.isValueBelowZero(
                                            skill.skill_training_bonus,
                                        ),
                                    "text-gray-700 dark:text-white":
                                        skill.skill_training_bonus === 0,
                                }),
                            },
                            (skill.skill_training_bonus * 100).toFixed(2),
                            "%",
                        ),
                    ),
                ),
                showSeparator && !hasShown
                    ? React.createElement("div", {
                          className:
                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4 sm:block md:hidden",
                      })
                    : null,
            );
            if (showSeparator) {
                hasShown = true;
            }
            return element;
        });
    };
    ExpandedComparison.prototype.shouldUseMobileHeightRestrictions = function (
        customWidth,
    ) {
        if (!this.props.mobile_data) {
            return false;
        }
        return (
            this.props.mobile_data.view_port < customWidth &&
            this.props.mobile_data.mobile_height_restriction
        );
    };
    ExpandedComparison.prototype.render = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(ItemNameColorationText, {
                item: this.props.comparison_details,
                custom_width: false,
                additional_css: "my-4",
            }),
            React.createElement(
                "div",
                {
                    className: clsx({
                        "max-h-[250px] overflow-y-scroll":
                            this.shouldUseMobileHeightRestrictions(1500),
                        "max-h-[200px] overflow-y-scroll":
                            this.shouldUseMobileHeightRestrictions(800),
                    }),
                },
                React.createElement(
                    "div",
                    { className: "my-4" },
                    this.renderAttackOrDefenceAdjustment(),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                }),
                React.createElement(
                    "div",
                    { className: "grid md:grid-cols-2 gap-2" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement("strong", null, "Stat Adjustment"),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            this.renderCoreAttributes(),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                        }),
                        React.createElement(
                            "strong",
                            null,
                            "Enemy Stat Reductions",
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            this.renderEnemyStatReductions(),
                        ),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4 sm:block md:hidden",
                    }),
                    React.createElement(
                        "div",
                        null,
                        React.createElement("strong", null, "Counter & Ambush"),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Counter Chance"),
                            React.createElement(
                                "dd",
                                {
                                    className: clsx({
                                        "text-green-700 dark:text-green-500":
                                            this.isValueAboveZero(
                                                this.props.comparison_details
                                                    .counter_chance_adjustment,
                                            ),
                                        "text-red-700 dark:text-red-500":
                                            this.isValueBelowZero(
                                                this.props.comparison_details
                                                    .counter_chance_adjustment,
                                            ),
                                        "text-gray-700 dark:text-white":
                                            this.props.comparison_details
                                                .counter_chance_adjustment ===
                                            0,
                                    }),
                                },
                                (
                                    this.props.comparison_details
                                        .counter_chance_adjustment * 100
                                ).toFixed(2),
                                "%",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Counter Resistance Chance",
                            ),
                            React.createElement(
                                "dd",
                                {
                                    className: clsx({
                                        "text-green-700 dark:text-green-500":
                                            this.isValueAboveZero(
                                                this.props.comparison_details
                                                    .counter_resistance_adjustment,
                                            ),
                                        "text-red-700 dark:text-red-500":
                                            this.isValueBelowZero(
                                                this.props.comparison_details
                                                    .counter_resistance_adjustment,
                                            ),
                                        "text-gray-700 dark:text-white":
                                            this.props.comparison_details
                                                .counter_resistance_adjustment ===
                                            0,
                                    }),
                                },
                                (
                                    this.props.comparison_details
                                        .counter_resistance_adjustment * 100
                                ).toFixed(2),
                                "%",
                            ),
                            React.createElement("dt", null, "Ambush Chance"),
                            React.createElement(
                                "dd",
                                {
                                    className: clsx({
                                        "text-green-700 dark:text-green-500":
                                            this.isValueAboveZero(
                                                this.props.comparison_details
                                                    .ambush_chance_adjustment,
                                            ),
                                        "text-red-700 dark:text-red-500":
                                            this.isValueBelowZero(
                                                this.props.comparison_details
                                                    .ambush_chance_adjustment,
                                            ),
                                        "text-gray-700 dark:text-white":
                                            this.props.comparison_details
                                                .ambush_chance_adjustment === 0,
                                    }),
                                },
                                (
                                    this.props.comparison_details
                                        .ambush_chance_adjustment * 100
                                ).toFixed(2),
                                "%",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Ambush Resistance",
                            ),
                            React.createElement(
                                "dd",
                                {
                                    className: clsx({
                                        "text-green-700 dark:text-green-500":
                                            this.isValueAboveZero(
                                                this.props.comparison_details
                                                    .ambush_resistance_adjustment,
                                            ),
                                        "text-red-700 dark:text-red-500":
                                            this.isValueBelowZero(
                                                this.props.comparison_details
                                                    .ambush_resistance_adjustment,
                                            ),
                                        "text-gray-700 dark:text-white":
                                            this.props.comparison_details
                                                .ambush_resistance_adjustment ===
                                            0,
                                    }),
                                },
                                (
                                    this.props.comparison_details
                                        .ambush_resistance_adjustment * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                        this.renderSkillsChanges(),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                        }),
                        React.createElement("strong", null, "Misc. Modifiers"),
                        React.createElement("div", {
                            className:
                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                        }),
                        React.createElement(
                            "dl",
                            null,
                            React.createElement(
                                "dt",
                                null,
                                "Entrancing Chance",
                            ),
                            React.createElement(
                                "dd",
                                {
                                    className: clsx({
                                        "text-green-700 dark:text-green-500":
                                            this.isValueAboveZero(
                                                this.props.comparison_details
                                                    .entranced_chance,
                                            ),
                                        "text-red-700 dark:text-red-500":
                                            this.isValueBelowZero(
                                                this.props.comparison_details
                                                    .entranced_chance,
                                            ),
                                        "text-gray-700 dark:text-white":
                                            this.props.comparison_details
                                                .entranced_chance === 0,
                                    }),
                                },
                                (
                                    this.props.comparison_details
                                        .entranced_chance * 100
                                ).toFixed(2),
                                "%",
                            ),
                            React.createElement(
                                "dt",
                                null,
                                "Steal Life Chance",
                            ),
                            React.createElement(
                                "dd",
                                {
                                    className: clsx({
                                        "text-green-700 dark:text-green-500":
                                            this.isValueAboveZero(
                                                this.props.comparison_details
                                                    .steal_life_amount,
                                            ),
                                        "text-red-700 dark:text-red-500":
                                            this.isValueBelowZero(
                                                this.props.comparison_details
                                                    .steal_life_amount,
                                            ),
                                        "text-gray-700 dark:text-white":
                                            this.props.comparison_details
                                                .steal_life_amount === 0,
                                    }),
                                },
                                (
                                    this.props.comparison_details
                                        .steal_life_amount * 100
                                ).toFixed(2),
                                "%",
                            ),
                        ),
                    ),
                ),
            ),
        );
    };
    return ExpandedComparison;
})(React.Component);
export default ExpandedComparison;
//# sourceMappingURL=expanded-comparison.js.map
