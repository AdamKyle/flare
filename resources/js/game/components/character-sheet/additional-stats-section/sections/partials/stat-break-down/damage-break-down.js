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
import DangerButton from "../../../../../ui/buttons/danger-button";
import { startCase } from "lodash";
import Ajax from "../../../../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../../../ui/progress-bars/loading-progress-bar";
import ItemNameColorationText from "../../../../../items/item-name/item-name-coloration-text";
var DamageBreakDown = (function (_super) {
    __extends(DamageBreakDown, _super);
    function DamageBreakDown(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            error_message: null,
            is_loading: true,
            details: null,
        };
        return _this;
    }
    DamageBreakDown.prototype.componentDidMount = function () {
        var _this = this;
        this.setState(
            {
                error_message: "",
            },
            function () {
                if (_this.props.character === null) {
                    return;
                }
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            _this.props.character_id +
                            "/specific-attribute-break-down",
                    )
                    .setParameters({
                        type: _this.props.type,
                        is_voided: _this.props.is_voided ? 1 : 0,
                    })
                    .doAjaxCall(
                        "get",
                        function (response) {
                            _this.setState({
                                is_loading: false,
                                details: response.data.break_down,
                            });
                        },
                        function (error) {
                            _this.setState({ is_loading: false });
                            if (typeof error.response !== "undefined") {
                                _this.setState({
                                    error_message: error.response.data.mmessage,
                                });
                            }
                        },
                    );
            },
        );
    };
    DamageBreakDown.prototype.titelizeType = function () {
        return startCase(this.props.type.replace("-", " "));
    };
    DamageBreakDown.prototype.renderItemListEffects = function () {
        var _this = this;
        if (this.state.details === null) {
            return;
        }
        if (this.state.details.attached_affixes.length === 0) {
            return React.createElement(
                "p",
                { className: "my-4 text-slate-700 dark:text-slate-400" },
                "There is nothing equipped.",
            );
        }
        return this.state.details.attached_affixes.map(function (equippedItem) {
            var amount = 0;
            if (typeof equippedItem.base_damage !== "undefined") {
                amount = equippedItem.base_damage;
            } else {
                amount = equippedItem.base_healing;
            }
            return React.createElement(
                "li",
                null,
                React.createElement(ItemNameColorationText, {
                    item: equippedItem.item_details,
                    custom_width: false,
                }),
                " ",
                React.createElement(
                    "span",
                    { className: "text-green-700 dark:text-green-500" },
                    "(+",
                    amount,
                    ")",
                ),
                equippedItem.affixes.length > 0
                    ? React.createElement(
                          "ul",
                          {
                              className:
                                  "ps-5 mt-2 space-y-1 list-disc list-inside",
                          },
                          _this.renderAttachedAffixes(equippedItem.affixes),
                      )
                    : null,
            );
        });
    };
    DamageBreakDown.prototype.renderBoonIncreaseAllStatsEffects = function () {
        if (this.state.details === null) {
            return;
        }
        if (this.state.details.boon_details.length <= 0) {
            return;
        }
        if (this.state.details.boon_details.increases_all_stats.length <= 0) {
            return;
        }
        return this.state.details.boon_details.increases_all_stats.map(
            function (boonIncreaseAllStats) {
                return React.createElement(
                    "li",
                    null,
                    React.createElement(ItemNameColorationText, {
                        item: boonIncreaseAllStats.item_details,
                        custom_width: false,
                    }),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (boonIncreaseAllStats.increase_amount * 100).toFixed(2),
                        "%)",
                    ),
                );
            },
        );
    };
    DamageBreakDown.prototype.renderBoonIncreaseSpecificStatEffects =
        function () {
            if (this.state.details === null) {
                return;
            }
            if (
                this.state.details.boon_details.increases_single_stat.length <=
                0
            ) {
                return null;
            }
            return this.state.details.boon_details.increases_single_stat.map(
                function (boonIncreaseAllStats) {
                    return React.createElement(
                        "li",
                        null,
                        React.createElement(ItemNameColorationText, {
                            item: boonIncreaseAllStats.item_details,
                            custom_width: false,
                        }),
                        " ",
                        React.createElement(
                            "span",
                            { className: "text-green-700 dark:text-green-500" },
                            "(+",
                            (
                                boonIncreaseAllStats.increase_amount * 100
                            ).toFixed(2),
                            "%)",
                        ),
                    );
                },
            );
        };
    DamageBreakDown.prototype.renderAncestralItemSkill = function () {
        if (this.state.details === null) {
            return;
        }
        return this.state.details.ancestral_item_skill_data.map(
            function (ancestralItemSkill) {
                return React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-orange-600 dark:text-orange-300" },
                        ancestralItemSkill.name,
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (ancestralItemSkill.increase_amount * 100).toFixed(2),
                        "%)",
                    ),
                );
            },
        );
    };
    DamageBreakDown.prototype.renderSkillsAffectingDamage = function () {
        if (this.state.details === null) {
            return;
        }
        return this.state.details.skills_effecting_damage.map(
            function (skillAffectingDamage) {
                return React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-orange-600 dark:text-orange-300" },
                        skillAffectingDamage.name,
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (skillAffectingDamage.amount * 100).toFixed(2),
                        "%)",
                    ),
                );
            },
        );
    };
    DamageBreakDown.prototype.renderClassMasteries = function () {
        if (this.state.details === null) {
            return;
        }
        return this.state.details.masteries.map(function (mastery) {
            return React.createElement(
                "li",
                null,
                React.createElement(
                    "span",
                    { className: "text-primary-600 dark:text-primary-300" },
                    startCase(mastery.name.replace("-", " ")),
                    " for position:",
                    " ",
                    startCase(mastery.position.replace("-", " ")),
                ),
                " ",
                React.createElement(
                    "span",
                    { className: "text-green-700 dark:text-green-500" },
                    "(+",
                    (mastery.amount * 100).toFixed(2),
                    "%)",
                ),
            );
        });
    };
    DamageBreakDown.prototype.renderClassSpecialtiesStatIncrease = function () {
        if (this.state.details === null) {
            return;
        }
        if (this.state.details.class_specialties === null) {
            return null;
        }
        return this.state.details.class_specialties.map(
            function (classSpecialty) {
                return React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-sky-600 dark:text-sky-500" },
                        classSpecialty.name,
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (classSpecialty.amount * 100).toFixed(2),
                        "%)",
                    ),
                );
            },
        );
    };
    DamageBreakDown.prototype.renderAttachedAffixes = function (
        attachedAffixes,
    ) {
        return attachedAffixes.map(function (attachedAffix) {
            return React.createElement(
                "li",
                null,
                React.createElement(
                    "span",
                    { className: "text-slate-700 dark:text-slate-400" },
                    attachedAffix.name,
                ),
                " ",
                React.createElement(
                    "span",
                    { className: "text-green-700 dark:text-green-500" },
                    "(+",
                    (attachedAffix.amount * 100).toFixed(2),
                    "%);",
                ),
            );
        });
    };
    DamageBreakDown.prototype.renderUnequippedDamageDetails = function () {
        return React.createElement(
            "div",
            null,
            React.createElement("h4", null, "Non Equipped Damage"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "ul",
                {
                    className:
                        "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                },
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Total Damage",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.non_equipped_damage_amount,
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Damage Stat Name",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(",
                        startCase(
                            (this.props.is_voided ? "Voided " : "") +
                                this.state.details.damage_stat_name,
                        ),
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Damage Stat Amount",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.damage_stat_amount,
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Percentage of stat used",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (
                            this.state.details
                                .non_equipped_percentage_of_stat_used * 100
                        ).toFixed(2),
                        ")",
                    ),
                ),
            ),
        );
    };
    DamageBreakDown.prototype.renderWeaponDamageDetails = function () {
        return React.createElement(
            "div",
            null,
            React.createElement("h4", null, "How base damage is calculated"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "p",
                { className: "my-4" },
                "Base damage is what we use to determine your over all damage, this is a portion of your stats, usually 5%. Some classes can raise this percent higher.",
            ),
            React.createElement(
                "ul",
                {
                    className:
                        "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                },
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Base Damage",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.base_damage,
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Amount of stat used",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (this.state.details.percentage_of_stat * 100).toFixed(
                            2,
                        ),
                        "%)",
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement("h4", null, "Weapon damage from items"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "ul",
                {
                    className:
                        "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                },
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Total Weapon Damage",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.total_damage_for_type,
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Damage Stat Name",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(",
                        startCase(this.state.details.damage_stat_name),
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Damage Stat Amount",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.damage_stat_amount,
                        ")",
                    ),
                ),
            ),
        );
    };
    DamageBreakDown.prototype.renderSpellDamage = function () {
        return React.createElement(
            "div",
            null,
            React.createElement("h4", null, "Spell damage from items"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "ul",
                {
                    className:
                        "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                },
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Damage Stat Name",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(",
                        startCase(this.state.details.damage_stat_name),
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Damage Stat Amount",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.damage_stat_amount,
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Damage stat amount to use",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.spell_damage_stat_amount_to_use,
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Percentage of stat used",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (
                            this.state.details.percentage_of_stat_used * 100
                        ).toFixed(2),
                        "%)",
                    ),
                ),
            ),
        );
    };
    DamageBreakDown.prototype.renderSpellHealing = function () {
        return React.createElement(
            "div",
            null,
            React.createElement("h4", null, "Spell healing from items"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "ul",
                {
                    className:
                        "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                },
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Healing Stat Name",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(",
                        startCase(this.state.details.damage_stat_name),
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Healing Stat Amount",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.damage_stat_amount,
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Healing stat amount to use",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.spell_damage_stat_amount_to_use,
                        ")",
                    ),
                ),
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Percentage of stat used",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        (
                            this.state.details.percentage_of_stat_used * 100
                        ).toFixed(2),
                        "%)",
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement("h4", null, "Spell healing from items"),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            React.createElement(
                "ul",
                {
                    className:
                        "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                },
                React.createElement(
                    "li",
                    null,
                    React.createElement(
                        "span",
                        { className: "text-slate-700 dark:text-slate-400" },
                        "Total healing",
                        " ",
                    ),
                    " ",
                    React.createElement(
                        "span",
                        { className: "text-green-700 dark:text-green-500" },
                        "(+",
                        this.state.details.total_damage_for_type,
                        ")",
                    ),
                ),
            ),
        );
    };
    DamageBreakDown.prototype.render = function () {
        if (this.state.loading || this.state.details === null) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "flex justify-between" },
                React.createElement(
                    "div",
                    { className: "flex items-center" },
                    React.createElement(
                        "h3",
                        { className: "mr-2" },
                        (this.props.is_voided ? "Voided " : "") +
                            startCase(this.props.type.replace("-", " ")),
                    ),
                    this.state.details.non_equipped_damage_amount === 0
                        ? React.createElement(
                              "span",
                              { className: "text-gray-700 dark:text-gray-400" },
                              "(Base",
                              " ",
                              this.props.type === "heal_for"
                                  ? "Healing:"
                                  : "Damage:",
                              " ",
                              this.state.details.base_damage,
                              ")",
                          )
                        : null,
                ),
                React.createElement(DangerButton, {
                    button_label: "Close",
                    on_click: this.props.close_section,
                }),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
            }),
            this.props.is_voided
                ? React.createElement(
                      "p",
                      { className: "my-4 text-blue-700 dark:text-blue-500" },
                      "Voided Weapon Damage means no enchantments from your gear is used. Voided Weapon Damage only comes into play when an enemy voids you in combat.",
                  )
                : null,
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-2" },
                React.createElement(
                    "div",
                    null,
                    this.state.details.non_equipped_damage_amount !== 0
                        ? React.createElement(
                              React.Fragment,
                              null,
                              this.renderUnequippedDamageDetails(),
                              React.createElement(
                                  "div",
                                  {
                                      className:
                                          "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                                  },
                                  " ",
                              ),
                          )
                        : null,
                    this.props.type === "weapon_damage" &&
                        this.state.details.non_equipped_damage_amount === 0
                        ? React.createElement(
                              React.Fragment,
                              null,
                              this.renderWeaponDamageDetails(),
                              React.createElement(
                                  "div",
                                  {
                                      className:
                                          "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                                  },
                                  " ",
                              ),
                          )
                        : null,
                    this.props.type === "spell_damage" &&
                        this.state.details.non_equipped_damage_amount === 0
                        ? React.createElement(
                              React.Fragment,
                              null,
                              this.renderSpellDamage(),
                              React.createElement(
                                  "div",
                                  {
                                      className:
                                          "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                                  },
                                  " ",
                              ),
                          )
                        : null,
                    this.props.type === "heal_for" &&
                        this.state.details.non_equipped_damage_amount === 0
                        ? React.createElement(
                              React.Fragment,
                              null,
                              this.renderSpellHealing(),
                              React.createElement(
                                  "div",
                                  {
                                      className:
                                          "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                                  },
                                  " ",
                              ),
                          )
                        : null,
                    React.createElement("h4", null, "Equipped Modifiers"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.attached_affixes !== null
                        ? React.createElement(
                              "ol",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-decimal list-inside dark:text-gray-400",
                              },
                              this.renderItemListEffects(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "You have nothing equipped.",
                          ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2 block md:hidden",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        null,
                        "Boons that increases all stats",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.boon_details !== null
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              this.renderBoonIncreaseAllStatsEffects(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "There are no boons applied that effect this specific stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        "Boons that increase: ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.boon_details !== null
                        ? this.state.details.boon_details.hasOwnProperty(
                              "increases_single_stat",
                          )
                            ? React.createElement(
                                  "ul",
                                  {
                                      className:
                                          "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                                  },
                                  this.renderBoonIncreaseSpecificStatEffects(),
                              )
                            : React.createElement(
                                  "p",
                                  null,
                                  "There are no boons applied that effect this specific stat.",
                              )
                        : React.createElement(
                              "p",
                              null,
                              "There are no boons applied that effect this specific stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        " ",
                        "Equipped Class Specials That Raise:",
                        " ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.class_specialties !== null
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              this.renderClassSpecialtiesStatIncrease(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "There are no class specials equipped that effect this stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        " ",
                        "Equipped Class Skill That Raise:",
                        " ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.class_bonus_details !== null
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              React.createElement(
                                  "li",
                                  null,
                                  React.createElement(
                                      "span",
                                      {
                                          className:
                                              "text-slate-600 dark:text-slate-300",
                                      },
                                      this.state.details.class_bonus_details
                                          .name,
                                  ),
                                  React.createElement(
                                      "span",
                                      {
                                          className:
                                              "text-green-700 dark:text-green-500",
                                      },
                                      "(+",
                                      (
                                          this.state.details.class_bonus_details
                                              .amount * 100
                                      ).toFixed(2),
                                      "%)",
                                  ),
                              ),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "You do not have a class skill that effects this stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        " ",
                        "Ancestral Item Skills That Raise:",
                        " ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.ancestral_item_skill_data !== null
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              this.renderAncestralItemSkill(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "There are no Ancestral Item Skills that effect this stat.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        " Skills That Increase: ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.skills_effecting_damage !== null
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              this.renderSkillsAffectingDamage(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "No Class Skills that effect your AC.",
                          ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-4",
                    }),
                    React.createElement(
                        "h4",
                        null,
                        " ",
                        "Class Rank Masteries That Increase:",
                        " ",
                        this.titelizeType(),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2",
                    }),
                    this.state.details.masteries.length > 0
                        ? React.createElement(
                              "ul",
                              {
                                  className:
                                      "space-y-4 text-gray-500 list-disc list-inside dark:text-gray-400",
                              },
                              this.renderClassMasteries(),
                          )
                        : React.createElement(
                              "p",
                              null,
                              "No Class Masteries that effect this stat.",
                          ),
                ),
            ),
        );
    };
    return DamageBreakDown;
})(React.Component);
export default DamageBreakDown;
//# sourceMappingURL=damage-break-down.js.map
