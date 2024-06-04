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
import OrangeProgressBar from "../../components/ui/progress-bars/orange-progress-bar";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import BountyFightAjax from "./ajax/bounty-fight-ajax";
import { serviceContainer } from "../../lib/containers/core-container";
import LoadingProgressBar from "../ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../ui/alerts/simple-alerts/success-alert";
import Revive from "../../sections/game-actions-section/components/fight-section/revive";
import WarningAlert from "../ui/alerts/simple-alerts/warning-alert";
import DangerAlert from "../ui/alerts/simple-alerts/danger-alert";
import HandleCraftingAjax from "./ajax/handle-crafting-ajax";
import { ItemType } from "../items/enums/item-type";
import InfoAlert from "../ui/alerts/simple-alerts/info-alert";
var FactionNpcTasks = (function (_super) {
    __extends(FactionNpcTasks, _super);
    function FactionNpcTasks(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            attacking: false,
            crafting: false,
            success_message: null,
            error_message: null,
            must_revive: false,
        };
        _this.fightAjax = serviceContainer().fetch(BountyFightAjax);
        _this.craftingAjax = serviceContainer().fetch(HandleCraftingAjax);
        return _this;
    }
    FactionNpcTasks.prototype.bountyTask = function (monsterId) {
        var _this = this;
        if (!monsterId) {
            return;
        }
        this.setState(
            {
                attacking: true,
                success_message: null,
                error_message: null,
            },
            function () {
                if (!monsterId) {
                    return;
                }
                _this.fightAjax.doAjaxCall(
                    _this,
                    {
                        monster_id: monsterId,
                        npc_id: _this.props.faction_loyalty_npc.npc_id,
                    },
                    _this.props.character_id,
                );
            },
        );
    };
    FactionNpcTasks.prototype.craftingTask = function (itemType, itemId) {
        var _this = this;
        if (!itemId) {
            return;
        }
        var armourTypes = [
            ItemType.BODY,
            ItemType.SHIELD,
            ItemType.LEGGINGS,
            ItemType.BOOTS,
            ItemType.SLEEVES,
            ItemType.GLOVES,
            ItemType.HELMET,
        ];
        var spellTypes = [ItemType.SPELL_DAMAGE, ItemType.SPELL_HEALING];
        var typeToCraft = itemType;
        if (armourTypes.includes(itemType)) {
            typeToCraft = "armour";
        }
        if (spellTypes.includes(itemType)) {
            typeToCraft = "spell";
        }
        this.setState(
            {
                crafting: true,
                success_message: null,
                error_message: null,
            },
            function () {
                _this.craftingAjax.doAjaxCall(
                    _this,
                    {
                        item_to_craft: itemId,
                        type: typeToCraft,
                        craft_for_event: false,
                        craft_for_npc: true,
                    },
                    _this.props.character_id,
                );
            },
        );
    };
    FactionNpcTasks.prototype.showCheckMark = function (fameTask) {
        if (fameTask.current_amount === fameTask.required_amount) {
            return React.createElement("i", {
                className: "fas fa-check text-green-700 dark:text-green-500",
            });
        }
        return;
    };
    FactionNpcTasks.prototype.isBountyActionDisabled = function () {
        return (
            !this.props.can_attack ||
            this.state.attacking ||
            this.state.must_revive ||
            !(
                this.props.faction_loyalty_npc.npc.game_map_id ===
                this.props.character_map_id
            ) ||
            !this.props.faction_loyalty_npc.currently_helping
        );
    };
    FactionNpcTasks.prototype.renderTasks = function (fameTasks, bounties) {
        var _this = this;
        return fameTasks
            .filter(function (fameTask) {
                return bounties
                    ? fameTask.type === "bounty"
                    : fameTask.type !== "bounty";
            })
            .map(function (fameTask) {
                return React.createElement(
                    React.Fragment,
                    null,
                    React.createElement(
                        "dt",
                        null,
                        bounties
                            ? fameTask.monster_name
                            : fameTask.item_name + " [" + fameTask.type + "]",
                    ),
                    React.createElement(
                        "dd",
                        { className: "flex flex-justify" },
                        React.createElement(
                            "div",
                            { className: "flex-1 mr-2" },
                            _this.showCheckMark(fameTask),
                            " ",
                            fameTask.current_amount,
                            " /",
                            " ",
                            fameTask.required_amount,
                        ),
                        React.createElement(
                            "div",
                            { className: "flex-1 ml-2" },
                            bounties
                                ? React.createElement(PrimaryOutlineButton, {
                                      button_label: "Attack",
                                      on_click: function () {
                                          return _this.bountyTask(
                                              fameTask.monster_id,
                                          );
                                      },
                                      disabled:
                                          _this.isBountyActionDisabled() ||
                                          fameTask.current_amount ===
                                              fameTask.required_amount,
                                  })
                                : React.createElement(SuccessOutlineButton, {
                                      button_label: "Craft",
                                      on_click: function () {
                                          _this.craftingTask(
                                              fameTask.type,
                                              fameTask.item_id,
                                          );
                                      },
                                      disabled:
                                          !_this.props.can_craft ||
                                          _this.state.crafting ||
                                          _this.state.must_revive ||
                                          fameTask.current_amount ===
                                              fameTask.required_amount ||
                                          !_this.props.faction_loyalty_npc
                                              .currently_helping,
                                  }),
                        ),
                    ),
                );
            });
    };
    FactionNpcTasks.prototype.updateMustRevive = function () {
        this.setState({
            must_revive: false,
        });
    };
    FactionNpcTasks.prototype.renderTaskSection = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                null,
                React.createElement("h3", { className: "my-2" }, " Bounties "),
                this.props.character_map_id !==
                    this.props.faction_loyalty_npc.npc.game_map_id
                    ? React.createElement(
                          WarningAlert,
                          { additional_css: "my-2" },
                          "You are not on the same plane as this NPC, you cannot take part in the bounty tasks.",
                      )
                    : null,
                React.createElement(
                    InfoAlert,
                    { additional_css: "my-2" },
                    "You attack type, when doing bounties via this tab, will be: ",
                    React.createElement("strong", null, this.props.attack_type),
                ),
                React.createElement(
                    "dl",
                    null,
                    this.renderTasks(
                        this.props.faction_loyalty_npc.faction_loyalty_npc_tasks
                            .fame_tasks,
                        true,
                    ),
                ),
                this.state.attacking
                    ? React.createElement(LoadingProgressBar, null)
                    : null,
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            React.createElement(
                "div",
                null,
                React.createElement("h3", { className: "my-2" }, " Crafting "),
                React.createElement(
                    "dl",
                    null,
                    this.renderTasks(
                        this.props.faction_loyalty_npc.faction_loyalty_npc_tasks
                            .fame_tasks,
                        false,
                    ),
                ),
                this.state.crafting
                    ? React.createElement(LoadingProgressBar, null)
                    : null,
            ),
        );
    };
    FactionNpcTasks.prototype.renderFactionNpcTasks = function () {
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                null,
                React.createElement(OrangeProgressBar, {
                    primary_label:
                        this.props.faction_loyalty_npc.npc.real_name +
                        " Fame LV: " +
                        this.props.faction_loyalty_npc.current_level +
                        "/" +
                        this.props.faction_loyalty_npc.max_level,
                    secondary_label:
                        this.props.faction_loyalty_npc.current_fame +
                        "/" +
                        this.props.faction_loyalty_npc.next_level_fame +
                        " Fame",
                    percentage_filled:
                        (this.props.faction_loyalty_npc.current_fame /
                            this.props.faction_loyalty_npc.next_level_fame) *
                        100,
                    push_down: false,
                }),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
            }),
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      { additional_css: "my-2" },
                      this.state.success_message,
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-2" },
                      this.state.error_message,
                  )
                : null,
            this.props.faction_loyalty_npc.faction_loyalty_npc_tasks.fame_tasks
                .length > 0
                ? React.createElement(
                      "div",
                      null,
                      this.renderTaskSection(),
                      React.createElement(
                          "p",
                          { className: "my-4" },
                          "Bounties must be completed on the respective plane and manually. Automation will not work for this.",
                      ),
                  )
                : React.createElement(
                      SuccessAlert,
                      { additional_css: "my-2" },
                      "You have completed all this NPC's tasks. By being aligned to this Faction, your kingdoms for the plane the NPC lives on, will receive a Item Defence bonus based on the level of the NPC and the amount of NPC's you have helped! This bonus is automatically applied to all present and future kingdoms.",
                  ),
        );
    };
    FactionNpcTasks.prototype.render = function () {
        if (!this.state.must_revive) {
            return this.renderFactionNpcTasks();
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                WarningAlert,
                { additional_css: "my-4" },
                this.state.success_message,
            ),
            React.createElement(Revive, {
                can_attack: this.props.can_attack,
                is_character_dead: true,
                character_id: this.props.character_id,
                revive_call_back: this.updateMustRevive.bind(this),
            }),
        );
    };
    return FactionNpcTasks;
})(React.Component);
export default FactionNpcTasks;
//# sourceMappingURL=faction-npc-tasks.js.map
