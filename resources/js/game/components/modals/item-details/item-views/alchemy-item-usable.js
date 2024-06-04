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
import { startCase } from "lodash";
var statKeys = [
    "str_mod",
    "dex_mod",
    "dur_mod",
    "int_mod",
    "chr_mod",
    "agi_mod",
    "focus_mod",
];
var baseModifierKeys = ["base_damage_mod", "base_ac_mod", "base_healing_mod"];
var baseSkillModifierKeys = [
    "base_damage_mod_bonus",
    "base_ac_mod_bonus",
    "base_healing_mod_bonus",
];
var miscSkillModifiers = [
    "increase_skill_bonus_by",
    "increase_skill_training_bonus_by",
    "fight_time_out_mod_bonus",
    "move_time_out_mod_bonus",
];
var AlchemyItemUsable = (function (_super) {
    __extends(AlchemyItemUsable, _super);
    function AlchemyItemUsable(props) {
        return _super.call(this, props) || this;
    }
    AlchemyItemUsable.prototype.shouldRenderColumns = function () {
        var _this = this;
        var validStats = statKeys.filter(function (key) {
            return _this.props.item[key] > 0;
        });
        var validModifiers = baseModifierKeys.filter(function (key) {
            return _this.props.item[key] > 0;
        });
        return validStats.length > 0 && validModifiers.length > 0;
    };
    AlchemyItemUsable.prototype.shouldRenderBaseSkillModifiers = function () {
        var _this = this;
        return (
            baseSkillModifierKeys.filter(function (key) {
                return _this.props.item[key] > 0;
            }).length > 0
        );
    };
    AlchemyItemUsable.prototype.shouldRenderSkillModifiers = function () {
        var _this = this;
        return (
            miscSkillModifiers.filter(function (key) {
                return _this.props.item[key] > 0;
            }).length > 0
        );
    };
    AlchemyItemUsable.prototype.renderKingdomDamage = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                { className: "mt-4 mb-4 text-sky-700 dark:text-sky-600" },
                "This item can only be used on kingdoms. The damage it does can stack if you drop multiple items. The damage can be reduced by the kingdoms over all defence bonuses.",
            ),
            React.createElement(
                "p",
                { className: "mb-4" },
                "When you use this item on a kingdom you will do the % of damage listed below for one item to all aspects of the kingdom.",
            ),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Damage to kingdom"),
                React.createElement(
                    "dd",
                    {
                        className: clsx({
                            "text-green-700 dark:text-green-500":
                                this.props.item.kingdom_damage > 0,
                        }),
                    },
                    (this.props.item.kingdom_damage * 100).toFixed(2),
                    "%",
                ),
            ),
        );
    };
    AlchemyItemUsable.prototype.renderStatSection = function () {
        var _this = this;
        var validStats = statKeys.filter(function (key) {
            return _this.props.item[key] > 0;
        });
        var statList = validStats.map(function (stat) {
            return React.createElement(
                React.Fragment,
                null,
                React.createElement(
                    "dt",
                    null,
                    startCase(stat).replace(/\s/g, " "),
                ),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    "+",
                    (_this.props.item[stat] * 100).toFixed(2),
                    "%",
                ),
            );
        });
        if (statList.length <= 0) {
            return;
        }
        return React.createElement("dl", null, statList);
    };
    AlchemyItemUsable.prototype.renderBaseModifiersSection = function () {
        var _this = this;
        var validModifiers = baseModifierKeys.filter(function (key) {
            return _this.props.item[key] > 0;
        });
        var modifiersList = validModifiers.map(function (baseModifier) {
            return React.createElement(
                React.Fragment,
                null,
                React.createElement(
                    "dt",
                    null,
                    startCase(baseModifier).replace(/\s/g, " "),
                ),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    "+",
                    (_this.props.item[baseModifier] * 100).toFixed(2),
                    "%",
                ),
            );
        });
        if (modifiersList.length <= 0) {
            return;
        }
        return React.createElement(
            React.Fragment,
            null,
            React.createElement("dl", null, modifiersList),
            this.shouldRenderBaseSkillModifiers()
                ? React.createElement("div", {
                      className:
                          "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                  })
                : null,
        );
    };
    AlchemyItemUsable.prototype.renderBaseSkillModifiers = function () {
        var _this = this;
        var validModifiers = baseSkillModifierKeys.filter(function (key) {
            return _this.props.item[key] > 0;
        });
        var modifiersList = validModifiers.map(function (baseSkillModifier) {
            return React.createElement(
                React.Fragment,
                null,
                React.createElement(
                    "dt",
                    null,
                    startCase(baseSkillModifier).replace(/\s/g, " "),
                ),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    "+",
                    (_this.props.item[baseSkillModifier] * 100).toFixed(2),
                    "%",
                ),
            );
        });
        if (modifiersList.length <= 0) {
            return;
        }
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                { className: "my-4" },
                "These modifiers apply to your class skill, which can be seen on your character sheet, under skills. The skill will be orange.",
            ),
            React.createElement("dl", null, modifiersList),
        );
    };
    AlchemyItemUsable.prototype.renderSkillSection = function () {
        var _this = this;
        var validModifiers = miscSkillModifiers.filter(function (key) {
            return _this.props.item[key] > 0;
        });
        var modifiersList = validModifiers.map(function (miscSkillModifier) {
            return React.createElement(
                React.Fragment,
                null,
                React.createElement(
                    "dt",
                    null,
                    startCase(miscSkillModifier).replace(/\s/g, " "),
                ),
                React.createElement(
                    "dd",
                    { className: "text-green-700 dark:text-green-500" },
                    "+",
                    (_this.props.item[miscSkillModifier] * 100).toFixed(2),
                    "%",
                ),
            );
        });
        if (modifiersList.length <= 0) {
            return;
        }
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                { className: "my-4" },
                "These modifiers will apply to the following skills:",
                " ",
                React.createElement(
                    "strong",
                    null,
                    this.props.item.skills.join(", "),
                ),
            ),
            React.createElement("dl", null, modifiersList),
        );
    };
    AlchemyItemUsable.prototype.renderUsableColumns = function () {
        return React.createElement(
            "div",
            { className: "grid md:grid-cols-2 gap-2" },
            React.createElement(
                "div",
                null,
                this.props.item.stat_increase
                    ? React.createElement(
                          "dl",
                          { className: "my-4" },
                          React.createElement(
                              "dt",
                              null,
                              "All Stat increase %",
                          ),
                          React.createElement(
                              "dd",
                              {
                                  className:
                                      "text-green-700 dark:text-green-500",
                              },
                              (this.props.item.stat_increase * 100).toFixed(2),
                              "%",
                          ),
                      )
                    : this.renderStatSection(),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3 block md:hidden",
            }),
            React.createElement(
                "div",
                null,
                this.renderBaseModifiersSection(),
                this.shouldRenderBaseSkillModifiers()
                    ? React.createElement("div", {
                          className:
                              "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                      })
                    : null,
                this.renderBaseSkillModifiers(),
            ),
            this.shouldRenderSkillModifiers()
                ? React.createElement("div", {
                      className:
                          "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                  })
                : null,
            this.renderSkillSection(),
        );
    };
    AlchemyItemUsable.prototype.renderSingleColumnDetails = function () {
        return React.createElement(
            React.Fragment,
            null,
            this.props.item.stat_increase
                ? React.createElement(
                      "dl",
                      { className: "my-4" },
                      React.createElement("dt", null, "All Stat increase %"),
                      React.createElement(
                          "dd",
                          { className: "text-green-700 dark:text-green-500" },
                          (this.props.item.stat_increase * 100).toFixed(2),
                          "%",
                      ),
                  )
                : this.renderStatSection(),
            this.renderBaseModifiersSection(),
            this.renderBaseSkillModifiers(),
            this.renderSkillSection(),
        );
    };
    AlchemyItemUsable.prototype.renderUsableSection = function () {
        var lastsFor = this.props.item.lasts_for;
        var lastForLabel = "minutes";
        if (lastsFor > 60) {
            lastsFor = lastsFor / 60;
            lastForLabel = "hours";
        }
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "p",
                { className: "mb-4 text-sky-700 dark:text-sky-500" },
                React.createElement("strong", null, "Lasts For: "),
                " ",
                lastsFor,
                " ",
                lastForLabel,
                ".",
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "dl",
                null,
                React.createElement("dt", null, "Can Stack?"),
                React.createElement(
                    "dd",
                    null,
                    this.props.item.can_stack ? "Yes" : "No",
                ),
                React.createElement(
                    "dt",
                    null,
                    "Grants additional level upon level up?",
                ),
                React.createElement(
                    "dd",
                    null,
                    this.props.item.gain_additional_level ? "Yes" : "No",
                ),
                React.createElement("dt", null, "XP Bonus per Kill (%)"),
                React.createElement(
                    "dd",
                    null,
                    (this.props.item.xp_bonus * 100).toFixed(2),
                    "%",
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            this.shouldRenderColumns()
                ? this.renderUsableColumns()
                : this.renderSingleColumnDetails(),
        );
    };
    AlchemyItemUsable.prototype.renderCoreView = function () {
        if (this.props.item.damages_kingdoms) {
            return this.renderKingdomDamage();
        }
        return this.renderUsableSection();
    };
    AlchemyItemUsable.prototype.render = function () {
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
            this.renderCoreView(),
        );
    };
    return AlchemyItemUsable;
})(React.Component);
export default AlchemyItemUsable;
//# sourceMappingURL=alchemy-item-usable.js.map
