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
import { formatNumber } from "../../lib/game/format-number";
import { startCase } from "lodash";
import { ItemType } from "./enums/item-type";
import clsx from "clsx";
var coreAttributes = ["str", "dex", "dur", "int", "chr", "agi", "focus"];
var Item = (function (_super) {
    __extends(Item, _super);
    function Item(props) {
        return _super.call(this, props) || this;
    }
    Item.prototype.getName = function () {
        return React.createElement(
            "span",
            { className: "text-gray-600 dark:text-white" },
            this.props.item.name,
        );
    };
    Item.prototype.renderAttackChange = function () {
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Attack"),
            typeof this.props.item.damage_adjustment === "undefined"
                ? React.createElement(
                      "dd",
                      { className: "text-green-700 dark:text-green-500" },
                      formatNumber(this.props.item.base_damage),
                  )
                : React.createElement(
                      "dd",
                      {
                          className: clsx({
                              "text-green-700 dark:text-green-500":
                                  this.isValueAboveZero(
                                      this.props.item.damage_adjustment,
                                  ),
                              "text-red-700 dark:text-red-500":
                                  this.isValueBeloZero(
                                      this.props.item.damage_adjustment,
                                  ),
                              "text-gray-700 dark:text-white":
                                  this.props.item.damage_adjustment === 0,
                          }),
                      },
                      formatNumber(this.props.item.damage_adjustment),
                  ),
        );
    };
    Item.prototype.renderDefenceChange = function () {
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Defence"),
            typeof this.props.item.ac_adjustment === "undefined"
                ? React.createElement(
                      "dd",
                      { className: "text-green-700 dark:text-green-500" },
                      formatNumber(this.props.item.base_ac),
                  )
                : React.createElement(
                      "dd",
                      {
                          className: clsx({
                              "text-green-700 dark:text-green-500":
                                  this.isValueAboveZero(
                                      this.props.item.ac_adjustment,
                                  ),
                              "text-red-700 dark:text-red-500":
                                  this.isValueBeloZero(
                                      this.props.item.ac_adjustment,
                                  ),
                              "text-gray-700 dark:text-white":
                                  this.props.item.ac_adjustment === 0,
                          }),
                      },
                      formatNumber(this.props.item.ac_adjustment),
                  ),
        );
    };
    Item.prototype.renderHealingChange = function () {
        var baseHealingMod =
            this.props.item.base_healing_mod !== null
                ? this.props.item.base_healing_mod
                : 0;
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Base Healing"),
            React.createElement(
                "dd",
                { className: "text-green-700 dark:text-green-500" },
                "+",
                this.props.item.raw_healing
                    ? formatNumber(this.props.item.raw_healing)
                    : formatNumber(this.props.item.base_healing),
            ),
            React.createElement("dt", null, "Base Healing Mod"),
            React.createElement(
                "dd",
                { className: "text-green-700 dark:text-green-500" },
                "+",
                (baseHealingMod * 100).toFixed(2),
                "%",
            ),
            React.createElement("dt", null, "Resurrection Chance"),
            React.createElement(
                "dd",
                { className: "text-green-700 dark:text-green-500" },
                "+",
                (this.props.item.resurrection_chance * 100).toFixed(2),
                "%",
            ),
        );
    };
    Item.prototype.renderAmbushAndCounterChange = function () {
        return React.createElement(
            "dl",
            null,
            React.createElement("dt", null, "Ambush Chance"),
            React.createElement(
                "dd",
                { className: "text-green-700 dark:text-green-500" },
                "+",
                (this.props.item.ambush_chance * 100).toFixed(2),
                "%",
            ),
            React.createElement("dt", null, "Ambush Resistance"),
            React.createElement(
                "dd",
                { className: "text-green-700 dark:text-green-500" },
                "+",
                (this.props.item.ambush_resistance_chance * 100).toFixed(2),
                "%",
            ),
            React.createElement("dt", null, "Counter Chance"),
            React.createElement(
                "dd",
                { className: "text-green-700 dark:text-green-500" },
                "+",
                (this.props.item.counter_chance * 100).toFixed(2),
                "%",
            ),
            React.createElement("dt", null, "Counter Resistance"),
            React.createElement(
                "dd",
                { className: "text-green-700 dark:text-green-500" },
                "+",
                (this.props.item.counter_resistance_chance * 100).toFixed(2),
                "%",
            ),
        );
    };
    Item.prototype.renderAttackOrDefenceAdjustment = function () {
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
        if (damageBased.includes(this.props.item.type)) {
            return this.renderAttackChange();
        }
        if (this.props.item.type === ItemType.SPELL_HEALING) {
            return this.renderHealingChange();
        }
        if (this.props.item.type === ItemType.TRINKET) {
            return this.renderAmbushAndCounterChange();
        }
        return this.renderDefenceChange();
    };
    Item.prototype.isValueBeloZero = function (value) {
        return value < 0;
    };
    Item.prototype.isValueAboveZero = function (value) {
        return value > 0;
    };
    Item.prototype.mapCoreAttributes = function (attributeName) {
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
    Item.prototype.renderCoreAttributes = function () {
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
                                    _this.props.item[attribute + "_modifier"],
                                ),
                            "text-red-700 dark:text-red-500":
                                _this.isValueBeloZero(
                                    _this.props.item[attribute + "_modifier"],
                                ),
                            "text-gray-700 dark:text-white":
                                _this.props.item[attribute + "_modifier"] === 0,
                        }),
                    },
                    (_this.props.item[attribute + "_modifier"] * 100).toFixed(
                        2,
                    ) + "%",
                ),
            );
        });
    };
    Item.prototype.render = function () {
        return React.createElement(
            "div",
            null,
            React.createElement("h3", null, this.getName()),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 sm:grid-gcols-1 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "dl",
                        null,
                        this.renderCoreAttributes(),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 md:hidden sm:block",
                }),
                React.createElement(
                    "div",
                    null,
                    this.renderAttackOrDefenceAdjustment(),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3",
                    }),
                    this.props.item.crafting_type !== null
                        ? React.createElement(
                              React.Fragment,
                              null,
                              this.props.item.type !== ItemType.TRINKET
                                  ? React.createElement("div", {
                                        className:
                                            "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-4",
                                    })
                                  : null,
                              React.createElement(
                                  "dl",
                                  null,
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Crafting Type:",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      startCase(this.props.item.crafting_type),
                                  ),
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Skill Level Required",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      this.props.item.skill_level_req,
                                  ),
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Skill Level Trivial",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      this.props.item.skill_level_trivial,
                                  ),
                              ),
                          )
                        : null,
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "p",
                { className: "bold" },
                React.createElement(
                    "a",
                    { href: "/items/" + this.props.item.id, target: "_blank" },
                    "View more details about this item",
                    " ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return Item;
})(React.Component);
export default Item;
//# sourceMappingURL=item.js.map
