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
import { formatNumber } from "../../../../../lib/game/format-number";
import ItemAffixDetails from "./item-affix-details";
import ItemHolyDetails from "./item-holy-details";
import OrangeButton from "../../../../../components/ui/buttons/orange-button";
import InventoryItemAttachedGems from "../inventory-item-attached-gems";
var ItemDetails = (function (_super) {
    __extends(ItemDetails, _super);
    function ItemDetails(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            affix: null,
            view_affix: false,
            holy_stacks: null,
            view_stacks: false,
            view_sockets: false,
        };
        return _this;
    }
    ItemDetails.prototype.manageAffixModal = function (affix) {
        this.setState({
            affix: typeof affix !== "undefined" ? affix : null,
            view_affix: !this.state.view_affix,
        });
    };
    ItemDetails.prototype.manageHolyStacksDetails = function (holyStacks) {
        this.setState({
            holy_stacks: typeof holyStacks !== "undefined" ? holyStacks : null,
            view_stacks: !this.state.view_stacks,
        });
    };
    ItemDetails.prototype.viewSockets = function () {
        this.setState({
            view_sockets: !this.state.view_sockets,
        });
    };
    ItemDetails.prototype.renderAtonementAmounts = function () {
        var atonements = this.props.item.item_atonements.atonements;
        var atonementData = [];
        for (var key in atonements) {
            atonementData.push(
                React.createElement(
                    Fragment,
                    null,
                    React.createElement("dt", null, key),
                    React.createElement(
                        "dd",
                        null,
                        (atonements[key] * 100).toFixed(2),
                        "%",
                    ),
                ),
            );
        }
        return atonementData;
    };
    ItemDetails.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            { className: "max-h-[400px] md:max-h-[600px] overflow-y-auto" },
            React.createElement("div", {
                className: "mb-4 mt-4 text-sky-700 dark:text-sky-500",
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
                            (this.props.item.str_modifier * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Dex Modifier"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.dex_modifier * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Agi Modifier"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.agi_modifier * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Chr Modifier"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.chr_modifier * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Dur Modifier"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.dur_modifier * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Int Modifier"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.int_modifier * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Focus Modifier"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.focus_modifier * 100).toFixed(2),
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
                        "Modifiers",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Base Damage"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.base_damage > 0
                                ? formatNumber(this.props.item.base_damage)
                                : 0,
                        ),
                        React.createElement("dt", null, "Base Ac"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.base_ac > 0
                                ? formatNumber(this.props.item.base_ac)
                                : 0,
                        ),
                        React.createElement("dt", null, "Base Healing"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.base_healing > 0
                                ? formatNumber(this.props.item.base_healing)
                                : 0,
                        ),
                        React.createElement("dt", null, "Base Damage Mod"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.base_damage_mod * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Base Ac Mod"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.base_ac_mod * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Base Healing Mod"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.base_healing_mod * 100).toFixed(2),
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
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-500" },
                        "Evasion and Reductions",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Spell Evasion"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.spell_evasion * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Healing Reduction"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.healing_reduction * 100).toFixed(
                                2,
                            ),
                            "%",
                        ),
                        React.createElement("dt", null, "Affix Dmg. Reduction"),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.item.affix_damage_reduction * 100
                            ).toFixed(2),
                            "%",
                        ),
                    ),
                    this.props.item.affix_count > 0
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement("div", {
                                  className:
                                      "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                              }),
                              React.createElement(
                                  "h4",
                                  {
                                      className:
                                          "text-sky-600 dark:text-sky-500",
                                  },
                                  "Attached Affixes",
                              ),
                              React.createElement("div", {
                                  className:
                                      "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                              }),
                              React.createElement(
                                  "div",
                                  { className: "mt-4" },
                                  React.createElement(
                                      "div",
                                      { className: "mb-4" },
                                      this.props.item.item_prefix !== null
                                          ? React.createElement(OrangeButton, {
                                                button_label:
                                                    this.props.item.item_prefix
                                                        .name,
                                                on_click: function () {
                                                    return _this.manageAffixModal(
                                                        _this.props.item
                                                            .item_prefix,
                                                    );
                                                },
                                                additional_css: "w-1/2",
                                            })
                                          : null,
                                  ),
                                  React.createElement(
                                      "div",
                                      null,
                                      this.props.item.item_suffix !== null
                                          ? React.createElement(OrangeButton, {
                                                button_label:
                                                    this.props.item.item_suffix
                                                        .name,
                                                on_click: function () {
                                                    return _this.manageAffixModal(
                                                        _this.props.item
                                                            .item_suffix,
                                                    );
                                                },
                                                additional_css: "w-1/2",
                                            })
                                          : null,
                                  ),
                              ),
                          )
                        : null,
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-3 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-500" },
                        "Sockets",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Sockets Available"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.socket_amount,
                        ),
                    ),
                    React.createElement(OrangeButton, {
                        button_label: "View Attached Gems",
                        on_click: this.viewSockets.bind(this),
                        additional_css: "my-4",
                    }),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-500" },
                        "Elemental Atonement",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        this.renderAtonementAmounts(),
                    ),
                ),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-500" },
                        "Primary Elemental Attack",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Primary Element"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.item_atonements.elemental_damage
                                .name,
                        ),
                        React.createElement("dt", null, "Damage"),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.item.item_atonements.elemental_damage
                                    .amount * 100
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
                { className: "grid md:grid-cols-3 gap-3 mb-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-500" },
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
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-500" },
                        "Resurrection Chance",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Chance"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.resurrection_chance * 100).toFixed(
                                2,
                            ),
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
                        "Holy Info",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "p",
                        { className: "mb-4" },
                        "Indicates how many can be applied to the item, via the",
                        " ",
                        React.createElement(
                            "a",
                            {
                                href: "/information/holy-items",
                                target: "_blank",
                            },
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                            "Purgatory Smith Work Bench.",
                        ),
                    ),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Holy Stacks"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.holy_stacks,
                        ),
                        React.createElement("dt", null, "Holy Stacks Applied"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.item.holy_stacks_applied,
                        ),
                        this.props.item.holy_stacks_applied > 0
                            ? React.createElement(
                                  Fragment,
                                  null,
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Holy Stack Bonus",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      (
                                          this.props.item
                                              .holy_stack_stat_bonus * 100
                                      ).toFixed(2),
                                      "%",
                                  ),
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Holy Stack Stat Bonus",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      (
                                          this.props.item
                                              .holy_stack_stat_bonus * 100
                                      ).toFixed(2),
                                      "%",
                                  ),
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Holy Stack Break Down",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      React.createElement(
                                          "button",
                                          {
                                              type: "button",
                                              className:
                                                  "text-orange-600 dark:text-orange-500 hover:text-orange-700 dark:hover:text-orange-400",
                                              onClick: function () {
                                                  return _this.manageHolyStacksDetails(
                                                      _this.props.item
                                                          .applied_stacks,
                                                  );
                                              },
                                          },
                                          "View Details",
                                      ),
                                  ),
                              )
                            : null,
                    ),
                ),
            ),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-3 gap-3 mb-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h4",
                        { className: "text-sky-600 dark:text-sky-500" },
                        "Ambush Info",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Chance"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.ambush_chance * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Resistance"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.ambush_resistance * 100).toFixed(
                                2,
                            ),
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
                        "Counter",
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Chance"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.counter_chance * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Resistance"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.item.counter_resistance * 100).toFixed(
                                2,
                            ),
                            "%",
                        ),
                    ),
                ),
            ),
            this.state.view_affix && this.state.affix !== null
                ? React.createElement(ItemAffixDetails, {
                      is_open: this.state.view_affix,
                      affix: this.state.affix,
                      manage_modal: this.manageAffixModal.bind(this),
                  })
                : null,
            this.state.view_stacks && this.state.holy_stacks !== null
                ? React.createElement(ItemHolyDetails, {
                      is_open: this.state.view_stacks,
                      holy_stacks: this.state.holy_stacks,
                      manage_modal: this.manageHolyStacksDetails.bind(this),
                  })
                : null,
            this.state.view_sockets
                ? React.createElement(InventoryItemAttachedGems, {
                      is_open: this.state.view_sockets,
                      character_id: this.props.character_id,
                      item_id: this.props.item.id,
                      manage_modal: this.viewSockets.bind(this),
                  })
                : null,
        );
    };
    return ItemDetails;
})(React.Component);
export default ItemDetails;
//# sourceMappingURL=item-details.js.map
