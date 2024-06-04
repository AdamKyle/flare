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
import { ItemType } from "../items/enums/item-type";
import { startCase } from "lodash";
var coreAttributes = ["str", "dex", "dur", "int", "chr", "agi", "focus"];
var Comparison = (function (_super) {
    __extends(Comparison, _super);
    function Comparison(props) {
        return _super.call(this, props) || this;
    }
    Comparison.prototype.isValueBeloZero = function (value) {
        return value < 0;
    };
    Comparison.prototype.isValueAboveZero = function (value) {
        return value > 0;
    };
    Comparison.prototype.mapCoreAttributes = function (attributeName) {
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
    Comparison.prototype.renderCoreAttributes = function () {
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
                                    _this.props.comparison[
                                        attribute + "_adjustment"
                                    ],
                                ),
                            "text-red-700 dark:text-red-500":
                                _this.isValueBeloZero(
                                    _this.props.comparison[
                                        attribute + "_adjustment"
                                    ],
                                ),
                            "text-gray-700 dark:text-white":
                                _this.props.comparison[
                                    attribute + "_adjustment"
                                ] === 0,
                        }),
                    },
                    (
                        _this.props.comparison[attribute + "_adjustment"] * 100
                    ).toFixed(2) + "%",
                ),
            );
        });
    };
    Comparison.prototype.renderAttackChange = function () {
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
                                this.props.comparison.damage_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.damage_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.damage_adjustment === 0,
                    }),
                },
                formatNumber(this.props.comparison.damage_adjustment),
            ),
        );
    };
    Comparison.prototype.renderDefenceChange = function () {
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
                                this.props.comparison.ac_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.ac_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.ac_adjustment === 0,
                    }),
                },
                formatNumber(this.props.comparison.ac_adjustment),
            ),
        );
    };
    Comparison.prototype.renderAmbushAndCounterChange = function () {
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Ambush Chance"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.ambush_chance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.ambush_chance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.ambush_chance_adjustment ===
                            0,
                    }),
                },
                (this.props.comparison.ambush_chance_adjustment * 100).toFixed(
                    2,
                ),
                "%",
            ),
            React.createElement("dt", null, "Ambush Resistance"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison
                                    .ambush_resistance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.ambush_resistance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison
                                .ambush_resistance_adjustment === 0,
                    }),
                },
                (
                    this.props.comparison.ambush_resistance_adjustment * 100
                ).toFixed(2),
                "%",
            ),
            React.createElement("dt", null, "Counter Chance"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.counter_chance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.counter_chance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.counter_chance_adjustment ===
                            0,
                    }),
                },
                (this.props.comparison.counter_chance_adjustment * 100).toFixed(
                    2,
                ),
                "%",
            ),
            React.createElement("dt", null, "Counter Resistance"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison
                                    .counter_resistance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.counter_resistance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison
                                .counter_resistance_adjustment === 0,
                    }),
                },
                (
                    this.props.comparison.counter_resistance_adjustment * 100
                ).toFixed(2),
                "%",
            ),
        );
    };
    Comparison.prototype.renderHealingChange = function () {
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Base Healing"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.healing_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.healing_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.healing_adjustment === 0,
                    }),
                },
                formatNumber(this.props.comparison.healing_adjustment),
            ),
            React.createElement("dt", null, "Base Healing Mod"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.base_healing_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.base_healing_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.base_healing_adjustment === 0,
                    }),
                },
                (this.props.comparison.base_healing_adjustment * 100).toFixed(
                    2,
                ),
                "%",
            ),
            React.createElement("dt", null, "Resurrection Chance"),
            React.createElement(
                "dd",
                {
                    className: clsx({
                        "text-green-700 dark:text-green-500":
                            this.isValueAboveZero(
                                this.props.comparison.res_chance_adjustment,
                            ),
                        "text-red-700 dark:text-red-500": this.isValueBeloZero(
                            this.props.comparison.res_chance_adjustment,
                        ),
                        "text-gray-700 dark:text-white":
                            this.props.comparison.res_chance_adjustment === 0,
                    }),
                },
                (this.props.comparison.res_chance_adjustment * 100).toFixed(2),
                "%",
            ),
        );
    };
    Comparison.prototype.renderAttackOrDefenceAdjustment = function () {
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
        if (damageBased.includes(this.props.comparison.type)) {
            return this.renderAttackChange();
        }
        if (this.props.comparison.type === ItemType.SPELL_HEALING) {
            return this.renderHealingChange();
        }
        if (this.props.comparison.type === ItemType.TRINKET) {
            return this.renderAmbushAndCounterChange();
        }
        return this.renderDefenceChange();
    };
    Comparison.prototype.render = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(
                "dl",
                null,
                React.createElement(
                    "dt",
                    { className: "text-orange-500 dark:text-orange-400" },
                    "Equipped Position",
                ),
                React.createElement(
                    "dd",
                    null,
                    startCase(this.props.comparison.position.replace("-", " ")),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            this.renderAttackOrDefenceAdjustment(),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "div",
                null,
                React.createElement("dl", null, this.renderCoreAttributes()),
            ),
        );
    };
    return Comparison;
})(React.Component);
export default Comparison;
//# sourceMappingURL=comparison.js.map
