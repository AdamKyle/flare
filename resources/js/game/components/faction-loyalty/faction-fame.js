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
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../components/ui/alerts/simple-alerts/success-alert";
import DangerOutlineButton from "../../components/ui/buttons/danger-outline-button";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import DropDown from "../../components/ui/drop-down/drop-down";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../lib/ajax/ajax";
import ActionsTimers from "../timers/actions-timers";
import FactionNpcSection from "./faction-npc-section";
import FactionNpcTasks from "./faction-npc-tasks";
import FactionLoyaltyListeners from "./event-listeners/faction-loyalty-listeners";
import { serviceContainer } from "../../lib/containers/core-container";
var FactionFame = (function (_super) {
    __extends(FactionFame, _super);
    function FactionFame(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_loading: true,
            is_processing: false,
            selected_npc: null,
            error_message: null,
            success_message: null,
            npcs: [],
            game_map_name: null,
            faction_loyalty: null,
            selected_faction_loyalty_npc: null,
            attack_type: null,
        };
        _this.factionLoyaltyListeners = serviceContainer().fetch(
            FactionLoyaltyListeners,
        );
        _this.factionLoyaltyListeners.initialize(_this, _this.props.user_id);
        _this.factionLoyaltyListeners.register();
        return _this;
    }
    FactionFame.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("faction-loyalty/" + this.props.character_id)
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState(
                        {
                            is_loading: false,
                            npcs: result.data.npcs,
                            game_map_name: result.data.map_name,
                            faction_loyalty: result.data.faction_loyalty,
                            attack_type: result.data.attack_type,
                        },
                        function () {
                            _this.setInitialSelectedFactionInfo(
                                result.data.faction_loyalty,
                                result.data.npcs,
                            );
                        },
                    );
                },
                function (error) {
                    _this.setState({ is_loading: false });
                    if (error.response) {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
        this.factionLoyaltyListeners.listen();
    };
    FactionFame.prototype.manageAssistingNpc = function (isHelping) {
        var _this = this;
        if (!this.state.selected_faction_loyalty_npc) {
            return;
        }
        this.setState(
            {
                error_message: null,
                is_processing: true,
            },
            function () {
                if (!_this.state.selected_faction_loyalty_npc) {
                    _this.setState({
                        error_message: null,
                        is_processing: false,
                    });
                    return;
                }
                new Ajax()
                    .setRoute(
                        "faction-loyalty/" +
                            (isHelping ? "stop-assisting" : "assist") +
                            "/" +
                            _this.props.character_id +
                            "/" +
                            _this.state.selected_faction_loyalty_npc.id,
                    )
                    .doAjaxCall("post", function (result) {
                        _this.setState(
                            {
                                is_processing: false,
                                success_message: result.data.message,
                                faction_loyalty: result.data.faction_loyalty,
                            },
                            function () {
                                _this.setInitialSelectedFactionInfo(
                                    result.data.faction_loyalty,
                                    _this.state.npcs,
                                );
                            },
                        );
                    });
            },
        );
    };
    FactionFame.prototype.setInitialSelectedFactionInfo = function (
        factionLoyalty,
        npcs,
    ) {
        var helpingNpc = factionLoyalty.faction_loyalty_npcs.filter(
            function (factionLoyaltyNpc) {
                return factionLoyaltyNpc.currently_helping;
            },
        );
        if (helpingNpc.length === 0) {
            helpingNpc = factionLoyalty.faction_loyalty_npcs.filter(
                function (factionLoyaltyNpc) {
                    return factionLoyaltyNpc.npc_id === npcs[0].id;
                },
            );
            this.setState({
                selected_npc: npcs[0],
                selected_faction_loyalty_npc: helpingNpc[0],
            });
            this.props.update_faction_action_tasks(null);
            return;
        }
        var factionLoyaltyNpcHelping = helpingNpc[0];
        this.setState({
            selected_npc: npcs.filter(function (npc) {
                return npc.id === factionLoyaltyNpcHelping.npc_id;
            })[0],
            selected_faction_loyalty_npc: factionLoyaltyNpcHelping,
        });
        this.props.update_faction_action_tasks(
            factionLoyaltyNpcHelping.faction_loyalty_npc_tasks.fame_tasks.filter(
                function (fameTasks) {
                    return fameTasks.type !== "bounty";
                },
            ),
        );
        return helpingNpc[0];
    };
    FactionFame.prototype.buildNpcList = function (handler) {
        return this.state.npcs.map(function (npc) {
            return {
                name: npc.name,
                icon_class: "ra ra-aura",
                on_click: function () {
                    return handler(npc);
                },
            };
        });
    };
    FactionFame.prototype.selectedNpc = function () {
        var _this = this;
        var _a, _b;
        return (_b =
            (_a = this.state.npcs) === null || _a === void 0
                ? void 0
                : _a.find(function (npc) {
                      var _a;
                      return (
                          npc.name ===
                          ((_a = _this.state.selected_npc) === null ||
                          _a === void 0
                              ? void 0
                              : _a.name)
                      );
                  })) === null || _b === void 0
            ? void 0
            : _b.name;
    };
    FactionFame.prototype.switchToNpc = function (npc) {
        if (!this.state.faction_loyalty) {
            return;
        }
        this.setState({
            selected_npc: npc,
            selected_faction_loyalty_npc:
                this.state.faction_loyalty.faction_loyalty_npcs.filter(
                    function (factionLoyaltyNpc) {
                        return factionLoyaltyNpc.npc_id === npc.id;
                    },
                )[0],
        });
    };
    FactionFame.prototype.isAssisting = function () {
        if (!this.state.selected_faction_loyalty_npc) {
            return false;
        }
        return this.state.selected_faction_loyalty_npc.currently_helping;
    };
    FactionFame.prototype.render = function () {
        var _this = this;
        if (this.state.is_loading || this.state.faction_loyalty === null) {
            return React.createElement(
                "div",
                { className: "w-1/2 m-auto" },
                React.createElement(LoadingProgressBar, null),
            );
        }
        if (!this.state.selected_faction_loyalty_npc) {
            return React.createElement(
                DangerAlert,
                { additional_css: "my-4" },
                "Uh oh. We encountered an error here. Seems there is no Faction Loyalty info for this NPC. Not sure how that happened, but I would tell The Creator to investigate how the Faction Loyalty info is fetched for an NPC.",
            );
        }
        if (this.state.error_message !== null) {
            return React.createElement(
                DangerAlert,
                { additional_css: "my-4" },
                this.state.error_message,
            );
        }
        return React.createElement(
            "div",
            null,
            React.createElement(
                "div",
                { className: "py-4" },
                React.createElement(
                    "h2",
                    null,
                    this.state.game_map_name,
                    " Loyalty",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "Below you can select an NPC to assist. Each NPC will have it's own set of tasks to complete. Crafting tasks can be done any where, bounty tasks must be done manually and on the map of the NPC you are assisting.",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    "In order to gain fame, you must assist the NPC and by completing their tasks you will level the fame and gain the rewards as indicated but multiplied by the level of the npc's fame. You may only assist one NPC at a time and can freely switch at anytime.",
                ),
                React.createElement(
                    "p",
                    { className: "my-4" },
                    React.createElement(
                        "a",
                        {
                            href: "/information/faction-loyalty",
                            target: "_blank",
                            className: "my-2",
                        },
                        "Learn more about Faction Loyalties",
                        " ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                ),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    this.state.success_message
                        ? React.createElement(
                              SuccessAlert,
                              null,
                              this.state.success_message,
                          )
                        : null,
                ),
                React.createElement(
                    "div",
                    { className: "my-4" },
                    this.state.is_processing
                        ? React.createElement(LoadingProgressBar, null)
                        : null,
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                }),
                React.createElement(
                    "div",
                    { className: "my-4 flex flex-wrap md:flex-nowrap gap-2" },
                    React.createElement(
                        "div",
                        { className: "flex-none mt-[-25px] md:w-1/2" },
                        React.createElement(
                            "div",
                            {
                                className:
                                    "w-full relative left-0 flex flex-wrap",
                            },
                            React.createElement(
                                "div",
                                null,
                                React.createElement(DropDown, {
                                    menu_items: this.buildNpcList(
                                        this.switchToNpc.bind(this),
                                    ),
                                    button_title: "NPCs",
                                    selected_name: this.selectedNpc(),
                                }),
                            ),
                            React.createElement(
                                "div",
                                null,
                                this.isAssisting()
                                    ? React.createElement(DangerOutlineButton, {
                                          button_label: "Stop Assisting",
                                          on_click: function () {
                                              return _this.manageAssistingNpc(
                                                  true,
                                              );
                                          },
                                          additional_css: "mt-[34px] ml-4",
                                      })
                                    : React.createElement(
                                          PrimaryOutlineButton,
                                          {
                                              button_label: "Assist",
                                              on_click: function () {
                                                  return _this.manageAssistingNpc(
                                                      false,
                                                  );
                                              },
                                              additional_css: "mt-[34px] ml-4",
                                          },
                                      ),
                            ),
                            React.createElement(
                                "div",
                                null,
                                React.createElement(
                                    "div",
                                    { className: "mt-[38px] ml-4 font-bold" },
                                    React.createElement(
                                        "span",
                                        null,
                                        this.selectedNpc(),
                                    ),
                                ),
                            ),
                        ),
                        React.createElement(FactionNpcSection, {
                            character_id: this.props.character_id,
                            faction_loyalty_npc:
                                this.state.selected_faction_loyalty_npc,
                            can_craft: this.props.can_craft,
                            can_attack: this.props.can_attack,
                            character_map_id: this.props.character_map_id,
                            attack_type: this.state.attack_type,
                        }),
                    ),
                    React.createElement(
                        "div",
                        { className: "flex-none md:flex-auto w-full md:w-1/2" },
                        React.createElement(FactionNpcTasks, {
                            character_id: this.props.character_id,
                            faction_loyalty_npc:
                                this.state.selected_faction_loyalty_npc,
                            can_craft: this.props.can_craft,
                            can_attack: this.props.can_attack,
                            character_map_id: this.props.character_map_id,
                            attack_type: this.state.attack_type,
                        }),
                    ),
                ),
            ),
            React.createElement(ActionsTimers, { user_id: this.props.user_id }),
        );
    };
    return FactionFame;
})(React.Component);
export default FactionFame;
//# sourceMappingURL=faction-fame.js.map
