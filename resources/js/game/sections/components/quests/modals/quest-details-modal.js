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
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../../components/ui/loading/component-loading";
import Ajax from "../../../../lib/ajax/ajax";
import Tabs from "../../../../components/ui/tabs/tabs";
import TabPanel from "../../../../components/ui/tabs/tab-panel";
import clsx from "clsx";
import InfoAlert from "../../../../components/ui/alerts/simple-alerts/info-alert";
import CurrencyRequirement from "./components/currency-requirement";
import Reward from "./components/reward";
import LoadingProgressBar from "../../../../components/ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";
import WarningAlert from "../../../../components/ui/alerts/simple-alerts/warning-alert";
var QuestDetailsModal = (function (_super) {
    __extends(QuestDetailsModal, _super);
    function QuestDetailsModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            quest_details: null,
            loading: true,
            handing_in: false,
            success_message: null,
            error_message: null,
        };
        _this.tabs = [
            {
                name: "Npc Details",
                key: "npc-details",
            },
            {
                name: "Required To Complete",
                key: "required-to-complete",
            },
            {
                name: "Quest Reward",
                key: "quest-reward",
            },
        ];
        return _this;
    }
    QuestDetailsModal.prototype.componentDidMount = function () {
        var _this = this;
        if (this.props.quest_id === null) {
            return;
        }
        new Ajax()
            .setRoute(
                "quest/" + this.props.quest_id + "/" + this.props.character_id,
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        quest_details: result.data,
                        loading: false,
                    });
                },
                function (error) {},
            );
    };
    QuestDetailsModal.prototype.handInQuest = function () {
        var _this = this;
        this.setState(
            {
                handing_in: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "quest/" +
                            _this.props.quest_id +
                            "/hand-in-quest/" +
                            _this.props.character_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                handing_in: false,
                                success_message: result.data.message,
                            });
                            var data = result.data;
                            delete data.message;
                            _this.props.update_quests(data);
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                var message = response.data.hasOwnProperty(
                                    "message",
                                )
                                    ? response.data.message
                                    : response.data.error;
                                _this.setState({
                                    handing_in: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    };
    QuestDetailsModal.prototype.buildTitle = function () {
        if (this.state.quest_details === null) {
            return "Fetching details ...";
        }
        return this.state.quest_details.name;
    };
    QuestDetailsModal.prototype.getNPCCommands = function (npc) {
        return npc.commands
            .map(function (command) {
                return command.command;
            })
            .join(", ");
    };
    QuestDetailsModal.prototype.getRequiredQuestDetails = function () {
        if (this.state.quest_details !== null) {
            if (this.state.quest_details.parent_chain_quest !== null) {
                var questName =
                    this.state.quest_details.parent_chain_quest.name;
                var npcName =
                    this.state.quest_details.parent_chain_quest.npc.real_name;
                var mapName =
                    this.state.quest_details.parent_chain_quest
                        .belongs_to_map_name;
                return React.createElement(
                    "span",
                    null,
                    "You must complete another quest first, to start this story line. Complete the quest chain starting with:",
                    " ",
                    React.createElement("strong", null, questName),
                    " For the NPC:",
                    " ",
                    React.createElement("strong", null, npcName),
                    " who resides on:",
                    " ",
                    React.createElement("strong", null, mapName),
                    ".",
                );
            }
            if (this.state.quest_details.required_quest !== null) {
                var questName = this.state.quest_details.required_quest.name;
                var npcName =
                    this.state.quest_details.required_quest.npc.real_name;
                var mapName =
                    this.state.quest_details.required_quest.belongs_to_map_name;
                return React.createElement(
                    "span",
                    null,
                    "You must complete another quest first, to start this story line. Complete: ",
                    React.createElement("strong", null, questName),
                    " For the NPC: ",
                    React.createElement("strong", null, npcName),
                    " who resides on:",
                    " ",
                    React.createElement("strong", null, mapName),
                    ".",
                );
            }
        }
        return React.createElement("span", null, "Something went wrong.");
    };
    QuestDetailsModal.prototype.renderPlaneAccessRequirements = function (map) {
        if (map.map_required_item !== null) {
            return React.createElement(
                Fragment,
                null,
                React.createElement("dt", null, "Required to access"),
                React.createElement("dd", null, map.map_required_item.name),
                map.map_required_item.required_quest !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Which needs you to complete (Quest)",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              map.map_required_item.required_quest.name,
                          ),
                          React.createElement("dt", null, "By Speaking to"),
                          React.createElement(
                              "dd",
                              null,
                              map.map_required_item.required_quest.npc
                                  .real_name,
                          ),
                          React.createElement("dt", null, "Who is at (X/Y)"),
                          React.createElement(
                              "dd",
                              null,
                              map.map_required_item.required_quest.npc
                                  .x_position,
                              "/",
                              map.map_required_item.required_quest.npc
                                  .y_position,
                          ),
                          React.createElement("dt", null, "On plane"),
                          React.createElement(
                              "dd",
                              null,
                              map.map_required_item.required_quest.npc.game_map
                                  .name,
                          ),
                          this.renderPlaneAccessRequirements(
                              map.map_required_item.required_quest.npc.game_map,
                          ),
                      )
                    : null,
                map.map_required_item.required_monster !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Which requires you to fight (first)",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              map.map_required_item.required_monster.name,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Who resides on plane",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              map.map_required_item.required_monster.game_map
                                  .name,
                          ),
                          this.renderPlaneAccessRequirements(
                              map.map_required_item.required_monster.game_map,
                          ),
                      )
                    : null,
            );
        }
        return null;
    };
    QuestDetailsModal.prototype.renderLocations = function (locations) {
        var _this = this;
        return locations.map(function (location) {
            return React.createElement(
                Fragment,
                null,
                React.createElement(
                    "dl",
                    null,
                    React.createElement("dt", null, "By Going to"),
                    React.createElement("dd", null, location.name),
                    React.createElement("dt", null, "Which is at (X/Y)"),
                    React.createElement(
                        "dd",
                        null,
                        location.x,
                        "/",
                        location.y,
                    ),
                    React.createElement("dt", null, "On Plane"),
                    React.createElement("dd", null, location.map.name),
                    _this.renderPlaneAccessRequirements(location.map),
                ),
            );
        });
    };
    QuestDetailsModal.prototype.getMonsterTypeForRenderingItem = function (
        requiredMonster,
    ) {
        var type = "Regular Monster";
        if (requiredMonster.is_celestial_entity) {
            type = "Celestial";
        }
        if (requiredMonster.is_raid_monster) {
            type = "Raid Monster";
        }
        if (requiredMonster.is_raid_boss) {
            type = "Raid Boss";
        }
        return type;
    };
    QuestDetailsModal.prototype.renderItem = function (item) {
        return React.createElement(
            Fragment,
            null,
            item.drop_location_id !== null
                ? React.createElement(
                      "div",
                      { className: "mb-4" },
                      React.createElement(
                          InfoAlert,
                          null,
                          React.createElement(
                              "p",
                              { className: "mb-2" },
                              "Some items, such as this one, only drop when you are at a special location. These locations increase enemy strength making them more of a challenge.",
                          ),
                          React.createElement(
                              "p",
                              { className: "mb-2" },
                              "These items have a small chance to drop while your looting skill is capped at 45% here.",
                          ),
                          React.createElement(
                              "p",
                              null,
                              React.createElement(
                                  "strong",
                                  null,
                                  "These items will not drop if you are using Exploration. You must manually farm these quest items.",
                              ),
                          ),
                      ),
                  )
                : null,
            item.required_monster !== null
                ? item.required_monster.is_celestial_entity
                    ? React.createElement(
                          "div",
                          { className: "mb-4" },
                          React.createElement(
                              InfoAlert,
                              null,
                              React.createElement(
                                  "p",
                                  { className: "mb-2" },
                                  "Some quests such as this one may have you fighting a Celestial entity. You can check the",
                                  " ",
                                  React.createElement(
                                      "a",
                                      {
                                          href: "/information/npcs",
                                          target: "_blank",
                                      },
                                      "help docs (NPC's)",
                                  ),
                                  " ",
                                  "to find out, based on which plane, which Summoning NPC you ned to speak to inorder to conjure the entity, there is only one per plane.",
                              ),
                              React.createElement(
                                  "p",
                                  null,
                                  "Celestial Entities below Dungeons plane, will not be included in the weekly spawn.",
                              ),
                          ),
                      )
                    : null
                : null,
            React.createElement(
                "dl",
                null,
                item.required_monster !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Obtained by killing",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              item.required_monster.name,
                              " ",
                              "(" +
                                  this.getMonsterTypeForRenderingItem(
                                      item.required_monster,
                                  ) +
                                  ")",
                          ),
                          React.createElement("dt", null, "Resides on plane"),
                          React.createElement(
                              "dd",
                              null,
                              item.required_monster.game_map.name,
                          ),
                          this.renderPlaneAccessRequirements(
                              item.required_monster.game_map,
                          ),
                      )
                    : null,
                item.required_quest !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "Obtained by completing",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              item.required_quest.name,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Which belongs to (NPC)",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              item.required_quest.npc.real_name,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Who is on the plane of",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              item.required_quest.npc.game_map.name,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "At coordinates (X/Y)",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              item.required_quest.npc.x_position,
                              " /",
                              " ",
                              item.required_quest.npc.y_position,
                          ),
                          this.renderPlaneAccessRequirements(
                              item.required_quest.npc.game_map,
                          ),
                      )
                    : null,
                item.drop_location_id !== null
                    ? React.createElement(
                          Fragment,
                          null,
                          React.createElement(
                              "dt",
                              null,
                              "By Visiting (Fighting monsters for it to drop)",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              item.drop_location.name,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "At coordinates (X/Y)",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              item.drop_location.x,
                              " / ",
                              item.drop_location.y,
                          ),
                          React.createElement(
                              "dt",
                              null,
                              "Which is on the plane",
                          ),
                          React.createElement(
                              "dd",
                              null,
                              item.drop_location.map.name,
                          ),
                          this.renderPlaneAccessRequirements(
                              item.drop_location.map,
                          ),
                      )
                    : null,
            ),
            item.locations.length > 0
                ? React.createElement(
                      Fragment,
                      null,
                      React.createElement("hr", null),
                      React.createElement(
                          "h3",
                          { className: "tw-font-light" },
                          "Locations",
                      ),
                      React.createElement(
                          "p",
                          null,
                          "Locations that will give you the item, just for visiting.",
                      ),
                      React.createElement("hr", null),
                      this.renderLocations(item.locations),
                  )
                : null,
        );
    };
    QuestDetailsModal.prototype.fetchNpcPlaneAccess = function () {
        var npcPlaneAccess = null;
        if (!this.state.loading) {
            npcPlaneAccess = this.renderPlaneAccessRequirements(
                this.state.quest_details.npc.game_map,
            );
        }
        return npcPlaneAccess;
    };
    QuestDetailsModal.prototype.getText = function (text) {
        if (text === null) {
            return "";
        }
        return text.replace(/\n/g, "<br/>");
    };
    QuestDetailsModal.prototype.render = function () {
        var npcPLaneAccess = this.fetchNpcPlaneAccess();
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                secondary_actions: {
                    secondary_button_disabled:
                        !this.props.is_parent_complete ||
                        this.props.is_quest_complete,
                    secondary_button_label: "Hand in",
                    handle_action: this.handInQuest.bind(this),
                },
                title: this.buildTitle(),
                large_modal: false,
            },
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "h-24 mt-10 relative" },
                      React.createElement(ComponentLoading, null),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      !this.props.is_required_quest_complete
                          ? React.createElement(
                                WarningAlert,
                                { additional_css: "my-4" },
                                this.getRequiredQuestDetails(),
                            )
                          : null,
                      React.createElement(
                          Tabs,
                          { tabs: this.tabs, full_width: true },
                          React.createElement(
                              TabPanel,
                              { key: "npc-details" },
                              React.createElement(
                                  "div",
                                  {
                                      className: clsx({
                                          "grid md:grid-cols-2 gap-2 max-h-[200px] md:max-h-full overflow-y-auto md:overflow-y-visible":
                                              npcPLaneAccess !== null,
                                      }),
                                  },
                                  React.createElement(
                                      "div",
                                      null,
                                      React.createElement("div", {
                                          className:
                                              "border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden",
                                      }),
                                      React.createElement(
                                          "strong",
                                          null,
                                          "Basic Info",
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
                                              "Name",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.state.quest_details.npc
                                                  .real_name,
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Coordinates (X/Y)",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.state.quest_details.npc
                                                  .x_position,
                                              " ",
                                              "/",
                                              " ",
                                              this.state.quest_details.npc
                                                  .y_position,
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "On Plane",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              React.createElement(
                                                  "a",
                                                  {
                                                      href:
                                                          "/information/map/" +
                                                          this.state
                                                              .quest_details.npc
                                                              .game_map.id,
                                                      target: "_blank",
                                                  },
                                                  this.state.quest_details.npc
                                                      .game_map.name,
                                                  " ",
                                                  React.createElement("i", {
                                                      className:
                                                          "fas fa-external-link-alt",
                                                  }),
                                              ),
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Must be at same location?",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              this.state.quest_details.npc
                                                  .must_be_at_same_location
                                                  ? "Yes"
                                                  : "No",
                                          ),
                                      ),
                                  ),
                                  npcPLaneAccess !== null
                                      ? React.createElement(
                                            "div",
                                            {
                                                className: clsx({
                                                    "md:pl-2":
                                                        npcPLaneAccess !== null,
                                                }),
                                            },
                                            React.createElement("div", {
                                                className:
                                                    "border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden",
                                            }),
                                            React.createElement(
                                                "strong",
                                                null,
                                                "Npc Access Requirements",
                                            ),
                                            React.createElement("div", {
                                                className:
                                                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                                            }),
                                            React.createElement(
                                                "dl",
                                                {
                                                    className:
                                                        "md:ml-8 md:max-h-[250px] md:overflow-y-auto",
                                                },
                                                npcPLaneAccess,
                                            ),
                                        )
                                      : null,
                              ),
                              React.createElement(
                                  "div",
                                  {
                                      className:
                                          "my-4 max-h-[160px] overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-md bg-slate-200 dark:bg-slate-700 p-4 " +
                                          (!this.props.is_parent_complete ||
                                          !this.props.is_required_quest_complete
                                              ? " blur-sm"
                                              : ""),
                                  },
                                  this.props.is_quest_complete
                                      ? React.createElement("div", {
                                            dangerouslySetInnerHTML: {
                                                __html: this.getText(
                                                    this.state.quest_details
                                                        .after_completion_description,
                                                ),
                                            },
                                        })
                                      : React.createElement("div", {
                                            dangerouslySetInnerHTML: {
                                                __html: this.getText(
                                                    this.state.quest_details
                                                        .before_completion_description,
                                                ),
                                            },
                                        }),
                              ),
                          ),
                          React.createElement(
                              TabPanel,
                              { key: "required-to-complete" },
                              React.createElement(CurrencyRequirement, {
                                  quest: this.state.quest_details,
                                  item_requirements: this.renderItem.bind(this),
                              }),
                          ),
                          React.createElement(
                              TabPanel,
                              { key: "quest-reward" },
                              React.createElement(Reward, {
                                  quest: this.state.quest_details,
                              }),
                          ),
                      ),
                      this.state.success_message !== null
                          ? React.createElement(
                                "div",
                                { className: "mb-4 mt-4" },
                                React.createElement(
                                    SuccessAlert,
                                    null,
                                    this.state.success_message,
                                ),
                            )
                          : null,
                      this.state.error_message !== null
                          ? React.createElement(
                                "div",
                                { className: "mb-4 mt-4" },
                                React.createElement(
                                    DangerAlert,
                                    null,
                                    this.state.error_message,
                                ),
                            )
                          : null,
                      this.state.handing_in
                          ? React.createElement(LoadingProgressBar, null)
                          : null,
                  ),
        );
    };
    return QuestDetailsModal;
})(React.Component);
export default QuestDetailsModal;
//# sourceMappingURL=quest-details-modal.js.map
