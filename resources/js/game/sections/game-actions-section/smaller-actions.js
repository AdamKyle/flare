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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React, { Fragment } from "react";
import Select from "react-select";
import SmallCraftingSection from "../../components/crafting/general-crafting/small-crafting-section";
import ActionsTimers from "../../components/timers/actions-timers";
import MapTimer from "../../components/timers/map-timer";
import { updateTimers } from "../../lib/ajax/update-timers";
import SmallActionsManager from "../../lib/game/actions/small-actions-manager";
import { removeCommas } from "../../lib/game/format-number";
import CelestialFight from "./components/celestial-fight";
import DuelPlayer from "./components/duel-player";
import Revive from "./components/fight-section/revive";
import GamblingSection from "./components/gambling-section";
import JoinPvp from "./components/join-pvp";
import RaidSection from "./components/raid-section";
import MonsterActions from "./components/small-actions/monster-actions";
import SmallExplorationSection from "./components/small-actions/small-exploration-section";
import SmallMapMovementActions from "./components/small-actions/small-map-movement-actions";
import SmallerSpecialtyShop from "./components/small-actions/smaller-specialty-shop";
var SmallerActions = (function (_super) {
    __extends(SmallerActions, _super);
    function SmallerActions(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            selected_action: null,
            monsters: [],
            raid_monsters: [],
            characters_for_dueling: [],
            pvp_characters_on_map: [],
            attack_time_out: 0,
            crafting_time_out: 0,
            automation_time_out: 0,
            celestial_time_out: 0,
            movement_time_left: 0,
            crafting_type: null,
            duel_fight_info: null,
            loading: true,
            show_exploration: false,
            show_celestial_fight: false,
            show_duel_fight: false,
            show_join_pvp: false,
            show_hell_forged_section: false,
            show_purgatory_chains_section: false,
            show_gambling_section: false,
            show_twisted_earth_section: false,
        };
        _this.attackTimeOut = Echo.private(
            "show-timeout-bar-" + _this.props.character.user_id,
        );
        _this.craftingTimeOut = Echo.private(
            "show-crafting-timeout-bar-" + _this.props.character.user_id,
        );
        _this.mapTimeOut = Echo.private(
            "show-timeout-move-" + _this.props.character.user_id,
        );
        _this.explorationTimeOut = Echo.private(
            "exploration-timeout-" + _this.props.character.user_id,
        );
        _this.pvpUpdate = Echo.private(
            "update-pvp-attack-" + _this.props.character.user_id,
        );
        _this.celestialTimeout = Echo.private(
            "update-character-celestial-timeout-" +
                _this.props.character.user_id,
        );
        _this.duelOptions = Echo.join("update-duel");
        _this.smallActionsManager = new SmallActionsManager(_this);
        return _this;
    }
    SmallerActions.prototype.componentDidMount = function () {
        var _this = this;
        this.setState(
            __assign(
                __assign(__assign({}, this.state), this.props.action_data),
                { loading: false },
            ),
            function () {
                updateTimers(_this.props.character.id);
            },
        );
        this.attackTimeOut.listen(
            "Game.Core.Events.ShowTimeOutEvent",
            function (event) {
                _this.setState({
                    attack_time_out: event.forLength,
                });
            },
        );
        this.craftingTimeOut.listen(
            "Game.Core.Events.ShowCraftingTimeOutEvent",
            function (event) {
                _this.setState({
                    crafting_time_out: event.timeout,
                });
            },
        );
        this.mapTimeOut.listen(
            "Game.Maps.Events.ShowTimeOutEvent",
            function (event) {
                _this.setState({
                    movement_time_left: event.forLength,
                });
            },
        );
        this.celestialTimeout.listen(
            "Game.Core.Events.UpdateCharacterCelestialTimeOut",
            function (event) {
                _this.setState({
                    celestial_time_out: event.timeOut,
                });
            },
        );
        this.duelOptions.listen(
            "Game.Maps.Events.UpdateDuelAtPosition",
            function (event) {
                _this.setState(
                    {
                        pvp_characters_on_map: event.characters,
                        characters_for_dueling: [],
                    },
                    function () {
                        var characterLevel = removeCommas(
                            _this.props.character.level,
                        );
                        if (characterLevel >= 301) {
                            _this.smallActionsManager.setCharactersForDueling(
                                event.characters,
                            );
                        }
                    },
                );
            },
        );
        this.pvpUpdate.listen(
            "Game.Battle.Events.UpdateCharacterPvpAttack",
            function (event) {
                _this.setState({
                    show_duel_fight: true,
                    duel_fight_info: event.data,
                });
            },
        );
        this.explorationTimeOut.listen(
            "Game.Exploration.Events.ExplorationTimeOut",
            function (event) {
                _this.setState({
                    automation_time_out: event.forLength,
                });
            },
        );
    };
    SmallerActions.prototype.componentDidUpdate = function (
        prevProps,
        prevState,
        snapshot,
    ) {
        var _this = this;
        if (
            this.props.action_data !== null &&
            this.state.monsters.length === 0
        ) {
            this.setState(
                __assign(
                    __assign(__assign({}, this.state), this.props.action_data),
                    { loading: false },
                ),
                function () {
                    _this.props.update_parent_state({
                        monsters: _this.state.monsters,
                        raid_monsters: _this.state.raid_monsters,
                    });
                },
            );
        }
        if (this.props.action_data === null) {
            return;
        }
        if (this.props.action_data.monsters != this.state.monsters) {
            this.setState({
                monsters: this.props.action_data.monsters,
            });
        }
        if (this.props.action_data.raid_monsters != this.state.raid_monsters) {
            this.setState({
                raid_monsters: this.props.action_data.raid_monsters,
            });
        }
    };
    SmallerActions.prototype.componentWillUnmount = function () {
        this.props.update_parent_state({
            monsters: this.state.monsters,
            raid_monsters: this.state.raid_monsters,
        });
    };
    SmallerActions.prototype.setUpState = function () {
        if (this.props.action_data === null) {
            return;
        }
        var actionData = this.props.action_data;
        this.setState(
            __assign(__assign(__assign({}, this.state), actionData), {
                loading: false,
            }),
        );
    };
    SmallerActions.prototype.showAction = function (data) {
        this.smallActionsManager.setSelectedAction(data);
    };
    SmallerActions.prototype.closeMonsterSection = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.closeCraftingSection = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.closeMapSection = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.closeExplorationSection = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.closeFightCelestialSection = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.manageDuel = function () {
        this.setState({
            selected_action: null,
            show_duel_fight: !this.state.show_duel_fight,
        });
    };
    SmallerActions.prototype.manageJoinPvp = function () {
        this.setState({
            selected_action: null,
            show_join_pvp: !this.state.show_join_pvp,
        });
    };
    SmallerActions.prototype.manageHellForgedShop = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.managePurgatoryChainShop = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.manageTwistedEarthShop = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.removeSlots = function () {
        this.setState({
            selected_action: null,
        });
    };
    SmallerActions.prototype.resetDuelData = function () {
        this.setState({
            duel_fight_info: null,
        });
    };
    SmallerActions.prototype.createMonster = function () {
        if (this.state.raid_monsters.length > 0) {
            return React.createElement(RaidSection, {
                raid_monsters: this.state.raid_monsters,
                character_id: this.props.character.id,
                can_attack: this.props.character.can_attack,
                is_dead: this.props.character.is_dead,
                is_small: false,
                character_name: this.props.character.name,
                user_id: this.props.character.user_id,
                character_current_health: this.props.character.health,
            });
        }
        return React.createElement(MonsterActions, {
            monsters: this.state.monsters,
            character: this.props.character,
            close_monster_section: this.closeMonsterSection.bind(this),
            character_statuses: this.props.character_status,
            is_small: true,
        });
    };
    SmallerActions.prototype.showCrafting = function () {
        return React.createElement(SmallCraftingSection, {
            close_crafting_section: this.closeCraftingSection.bind(this),
            character: this.props.character,
            character_status: this.props.character_status,
            crafting_time_out: this.state.crafting_time_out,
            fame_tasks: this.props.fame_tasks,
        });
    };
    SmallerActions.prototype.renderExploration = function () {
        return React.createElement(SmallExplorationSection, {
            close_exploration_section: this.closeExplorationSection.bind(this),
            character: this.props.character,
            monsters: this.state.monsters,
        });
    };
    SmallerActions.prototype.showMapMovement = function () {
        return React.createElement(SmallMapMovementActions, {
            close_map_section: this.closeMapSection.bind(this),
            update_celestial: function (id) {},
            view_port: this.props.view_port,
            character: this.props.character,
            character_currencies: this.props.character_currencies,
            update_plane_quests: this.props.update_plane_quests,
            update_character_position: this.props.update_character_position,
            map_data: this.props.map_data,
            set_map_data: this.props.set_map_data,
        });
    };
    SmallerActions.prototype.showCelestialFight = function () {
        return React.createElement(CelestialFight, {
            character: this.props.character,
            manage_celestial_fight: this.closeFightCelestialSection.bind(this),
            celestial_id: this.props.celestial_id,
            update_celestial: this.props.update_celestial,
        });
    };
    SmallerActions.prototype.showDuelFight = function () {
        return React.createElement(DuelPlayer, {
            characters: this.state.characters_for_dueling,
            duel_data: this.state.duel_fight_info,
            character: this.props.character,
            manage_pvp: this.manageDuel.bind(this),
            reset_duel_data: this.resetDuelData.bind(this),
            is_small: true,
        });
    };
    SmallerActions.prototype.showSlots = function () {
        return React.createElement(GamblingSection, {
            character: this.props.character,
            close_gambling_section: this.removeSlots.bind(this),
            is_small: true,
        });
    };
    SmallerActions.prototype.showJoinPVP = function () {
        return React.createElement(JoinPvp, {
            manage_section: this.manageJoinPvp.bind(this),
            character_id: this.props.character.id,
        });
    };
    SmallerActions.prototype.showSpecialtyShop = function (type) {
        return React.createElement(SmallerSpecialtyShop, {
            show_hell_forged_section: type === "hell-forged-gear",
            show_purgatory_chains_section: type === "purgatory-chains-gear",
            show_twisted_earth_section: type === "twisted-earth-gear",
            character: this.props.character,
            manage_hell_forged_shop: this.manageHellForgedShop.bind(this),
            manage_purgatory_chain_shop:
                this.managePurgatoryChainShop.bind(this),
            manage_twisted_earth_shop: this.manageTwistedEarthShop.bind(this),
        });
    };
    SmallerActions.prototype.buildSection = function () {
        switch (this.state.selected_action) {
            case "fight":
                return this.createMonster();
            case "explore":
                return this.renderExploration();
            case "craft":
                return this.showCrafting();
            case "map-movement":
                return this.showMapMovement();
            case "celestial-fight":
                return this.showCelestialFight();
            case "pvp-fight":
                return this.showDuelFight();
            case "join-monthly-pvp":
                return this.showJoinPVP();
            case "hell-forged-gear":
                return this.showSpecialtyShop("hell-forged-gear");
            case "purgatory-chains-gear":
                return this.showSpecialtyShop("purgatory-chains-gear");
            case "twisted-earth-gear":
                return this.showSpecialtyShop("twisted-earth-gear");
            case "slots":
                return this.showSlots();
            default:
                return null;
        }
    };
    SmallerActions.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.state.selected_action !== null
                ? React.createElement(
                      React.Fragment,
                      null,
                      this.buildSection(),
                      React.createElement(
                          "div",
                          { className: "mt-8 mb-4" },
                          React.createElement(Revive, {
                              can_attack:
                                  this.props.character_status.can_attack,
                              is_character_dead: this.props.character.is_dead,
                              character_id: this.props.character.id,
                          }),
                      ),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      React.createElement(Select, {
                          onChange: this.showAction.bind(this),
                          options: this.smallActionsManager.buildOptions(),
                          menuPosition: "absolute",
                          menuPlacement: "bottom",
                          styles: {
                              menuPortal: function (base) {
                                  return __assign(__assign({}, base), {
                                      zIndex: 9999,
                                      color: "#000000",
                                  });
                              },
                          },
                          menuPortalTarget: document.body,
                          value: this.smallActionsManager.defaultSelectedAction(),
                      }),
                      React.createElement(Revive, {
                          can_attack: this.props.character_status.can_attack,
                          is_character_dead: this.props.character.is_dead,
                          character_id: this.props.character.id,
                      }),
                  ),
            React.createElement(
                "div",
                { className: "mt-4 mb-4" },
                React.createElement(
                    "div",
                    { className: "relative bottom-4" },
                    React.createElement(ActionsTimers, {
                        user_id: this.props.character.user_id,
                    }),
                ),
            ),
            React.createElement(
                "div",
                { className: "mt-4" },
                React.createElement(
                    "div",
                    { className: "relative" },
                    React.createElement(
                        "div",
                        { className: "" },
                        React.createElement(MapTimer, {
                            time_left: this.state.movement_time_left,
                            automation_time_out: this.state.automation_time_out,
                            celestial_time_out: this.state.celestial_time_out,
                        }),
                    ),
                ),
            ),
        );
    };
    return SmallerActions;
})(React.Component);
export default SmallerActions;
//# sourceMappingURL=smaller-actions.js.map
