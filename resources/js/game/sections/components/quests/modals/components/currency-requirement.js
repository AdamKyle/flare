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
import QuestHelpModal from "../quest-help-modal";
var CurrencyRequirement = (function (_super) {
    __extends(CurrencyRequirement, _super);
    function CurrencyRequirement(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            help_type: null,
            open_help: false,
        };
        return _this;
    }
    CurrencyRequirement.prototype.manageHelpDialogue = function (type) {
        this.setState({
            help_type: typeof type !== "undefined" ? type : null,
            open_help: !this.state.open_help,
        });
    };
    CurrencyRequirement.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "dl",
                null,
                this.props.quest.gold_cost !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement("dt", null, "Gold Cost:"),
                          React.createElement(
                              "dd",
                              null,
                              formatNumber(this.props.quest.gold_cost),
                          ),
                      )
                    : null,
                this.props.quest.gold_dust_cost !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement("dt", null, "Gold Dust Cost:"),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  formatNumber(this.props.quest.gold_dust_cost),
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "gold_dust",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                      )
                    : null,
                this.props.quest.shard_cost !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement("dt", null, "Shards Cost:"),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  formatNumber(this.props.quest.shard_cost),
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "shards",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                      )
                    : null,
                this.props.quest.copper_coin_cost > 0
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement("dt", null, "Copper Coin Cost:"),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  formatNumber(
                                      this.props.quest.copper_coin_cost,
                                  ),
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "copper_coins",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                      )
                    : null,
                this.props.quest.item !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement("dt", null, "Required Item:"),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  this.props.quest.item.name,
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "item_requirement",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                      )
                    : null,
                this.props.quest.secondary_item !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Secondary Required Item:",
                          ),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  this.props.quest.secondary_item.name,
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "secondary_item_requirement",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                      )
                    : null,
                this.props.quest.access_to_map_id !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Plane Access Required:",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              this.props.quest.required_plane.name,
                          ),
                      )
                    : null,
                this.props.quest.faction_game_map_id !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Plane Faction Name (Map to fight on)",
                          ),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  this.props.quest.faction_map.name,
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "faction_map",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                          React.createElement("dt", null, "Level required"),
                          React.createElement(
                              "dd",
                              null,
                              this.props.quest.required_faction_level,
                          ),
                      )
                    : null,
                this.props.quest.assisting_npc_id !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Assist the NPC with their tasks",
                          ),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  this.props.quest.faction_loyalty_npc
                                      .real_name,
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "fame_requirements",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                          React.createElement("dt", null, "On Game Map"),
                          React.createElement(
                              "dd",
                              null,
                              this.props.quest.faction_loyalty_npc.game_map
                                  .name,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Fame level required",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              this.props.quest.required_fame_level,
                          ),
                      )
                    : null,
                this.props.quest.required_quest_id !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Required Quest To Complete:",
                          ),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  this.props.quest.required_quest.name,
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "required_quest",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                      )
                    : null,
                this.props.quest.reincarnated_times !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Number of times to reincarnate:",
                          ),
                          React.createElement(
                              "dd",
                              { className: "flex items-center" },
                              React.createElement(
                                  "span",
                                  null,
                                  this.props.quest.reincarnated_times,
                              ),
                              React.createElement(
                                  "div",
                                  { className: "ml-2" },
                                  React.createElement(
                                      "button",
                                      {
                                          type: "button",
                                          onClick: function () {
                                              return _this.manageHelpDialogue(
                                                  "reincarnation_times",
                                              );
                                          },
                                          className:
                                              "text-blue-500 dark:text-blue-300",
                                      },
                                      React.createElement("i", {
                                          className: "fas fa-info-circle",
                                      }),
                                      " ",
                                      "Help",
                                  ),
                              ),
                          ),
                      )
                    : null,
            ),
            this.state.open_help && this.state.help_type !== null
                ? React.createElement(QuestHelpModal, {
                      manage_modal: this.manageHelpDialogue.bind(this),
                      type: this.state.help_type,
                      item_requirements: this.props.item_requirements,
                      quest: this.props.quest,
                  })
                : null,
        );
    };
    return CurrencyRequirement;
})(React.Component);
export default CurrencyRequirement;
//# sourceMappingURL=currency-requirement.js.map
