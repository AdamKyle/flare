var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
import React from "react";
import CraftingSection from "../../components/crafting/base-components/crafting-section";
import ActionsTimers from "../../components/timers/actions-timers";
import SkyOutlineButton from "../../components/ui/buttons/sky-outline-button";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import DropDown from "../../components/ui/drop-down/drop-down";
import ComponentLoading from "../../components/ui/loading/component-loading";
import { updateTimers } from "../../lib/ajax/update-timers";
import ActionsManager from "../../lib/game/actions/actions-manager";
import { removeCommas } from "../../lib/game/format-number";
import CelestialFight from "./components/celestial-fight";
import DuelPlayer from "./components/duel-player";
import ExplorationSection from "./components/exploration-section";
import GamblingSection from "./components/gambling-section";
import JoinPvp from "./components/join-pvp";
import RaidSection from "./components/raid-section";
import MonsterActions from "./components/small-actions/monster-actions";
import Shop from "./components/specialty-shops/shop";
var Actions = (function (_super) {
    __extends(Actions, _super);
    function Actions(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            monsters: [],
            raid_monsters: [],
            characters_for_dueling: [],
            pvp_characters_on_map: [],
            attack_time_out: 0,
            crafting_time_out: 0,
            crafting_type: null,
            duel_fight_info: null,
            loading: true,
            show_exploration: false,
            show_celestial_fight: false,
            show_duel_fight: false,
            show_join_pvp: false,
            show_hell_forged_section: false,
            show_purgatory_chains_section: false,
            show_twisted_earth_section: false,
            show_gambling_section: false,
        };
        _this.actionsManager = new ActionsManager(_this);
        _this.pvpUpdate = Echo.private("update-pvp-attack-" + _this.props.character.user_id);
        _this.traverseUpdate = Echo.private("update-plane-" + _this.props.character.user_id);
        _this.duelOptions = Echo.join("update-duel");
        return _this;
    }
    Actions.prototype.componentDidMount = function () {
        var _this = this;
        this.setUpState();
        this.duelOptions.listen("Game.Maps.Events.UpdateDuelAtPosition", function (event) {
            _this.setState({
                pvp_characters_on_map: event.characters,
                characters_for_dueling: [],
            }, function () {
                var characterLevel = removeCommas(_this.props.character.level);
                if (characterLevel >= 301) {
                    _this.actionsManager.setCharactersForDueling(event.characters);
                }
            });
        });
        this.pvpUpdate.listen("Game.Battle.Events.UpdateCharacterPvpAttack", function (event) {
            _this.setState({
                show_duel_fight: true,
                duel_fight_info: event.data,
            });
        });
        this.traverseUpdate.listen("Game.Maps.Events.UpdateMap", function (event) {
            var craftingType = _this.state.crafting_type;
            if (craftingType === "workbench" ||
                craftingType === "queen" ||
                craftingType === "labyrinth-oracle") {
                craftingType = null;
            }
            _this.setState({
                crafting_type: craftingType,
                show_hell_forged_section: false,
                show_purgatory_chains_section: false,
            });
        });
    };
    Actions.prototype.componentDidUpdate = function (prevProps) {
        if (this.props.action_data !== null && this.state.loading) {
            this.setState(__assign(__assign(__assign({}, this.state), this.props.action_data), { loading: false }));
        }
        if (this.props.action_data === null) {
            return;
        }
        if (this.props.action_data.monsters.length === 0) {
            return;
        }
        if (this.props.action_data.monsters[0].id !== this.state.monsters[0].id) {
            if (this.props.action_data.monsters.length > 0) {
                this.setState({
                    monsters: this.props.action_data.monsters,
                });
            }
        }
        if (this.props.action_data.raid_monsters !== this.state.raid_monsters) {
            this.setState({
                raid_monsters: this.props.action_data.raid_monsters,
            });
        }
        if (typeof this.props.character_position === "undefined") {
            return;
        }
        if (typeof prevProps.character_position === "undefined") {
            return;
        }
        if (this.props.character_position !== null &&
            prevProps.character_position !== null) {
            if ((this.props.character_position.x !==
                prevProps.character_position.x &&
                this.props.character_position.y !==
                    prevProps.character_position.y) ||
                this.props.character_position.game_map_id !==
                    prevProps.character_position.game_map_id) {
                this.setState({
                    show_celestial_fight: false,
                });
            }
        }
    };
    Actions.prototype.componentWillUnmount = function () {
        this.props.update_parent_state({
            monsters: this.state.monsters,
            raid_monsters: this.state.raid_monsters,
        });
    };
    Actions.prototype.setUpState = function () {
        var _this = this;
        if (this.props.action_data === null) {
            return;
        }
        var actionData = this.props.action_data;
        this.setState(__assign(__assign(__assign({}, this.state), actionData), { loading: false }), function () {
            updateTimers(_this.props.character.id);
            _this.props.update_parent_state({
                monsters: _this.state.monsters,
                raid_monsters: _this.state.raid_monsters,
            });
        });
    };
    Actions.prototype.openCrafting = function (type) {
        var _this = this;
        this.setState({
            show_purgatory_chains_section: false,
            show_hell_forged_section: false,
            show_twisted_earth_section: false,
        }, function () {
            _this.actionsManager.setCraftingType(type);
        });
    };
    Actions.prototype.manageExploration = function () {
        this.setState({
            show_exploration: !this.state.show_exploration,
        });
    };
    Actions.prototype.manageHellForgedShop = function () {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_join_pvp: false,
            show_duel_fight: false,
            show_celestial_fight: false,
            show_twisted_earth_section: false,
            show_hell_forged_section: !this.state.show_hell_forged_section,
        });
    };
    Actions.prototype.managedPurgatoryChainsShop = function () {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_join_pvp: false,
            show_duel_fight: false,
            show_celestial_fight: false,
            show_twisted_earth_section: false,
            show_purgatory_chains_section: !this.state.show_purgatory_chains_section,
        });
    };
    Actions.prototype.managedTwistedEarthShop = function () {
        this.setState({
            crafting_type: null,
            show_exploration: false,
            show_join_pvp: false,
            show_duel_fight: false,
            show_celestial_fight: false,
            show_purgatory_chains_section: false,
            show_twisted_earth_section: !this.state.show_twisted_earth_section,
        });
    };
    Actions.prototype.manageDuel = function () {
        this.setState({
            show_duel_fight: !this.state.show_duel_fight,
        });
    };
    Actions.prototype.manageJoinPvp = function () {
        this.setState({
            show_join_pvp: !this.state.show_join_pvp,
        });
    };
    Actions.prototype.manageFightCelestial = function () {
        this.setState({
            show_celestial_fight: !this.state.show_celestial_fight,
        });
    };
    Actions.prototype.manageGamblingSection = function () {
        this.setState({
            show_gambling_section: !this.state.show_gambling_section,
        });
    };
    Actions.prototype.isLoading = function () {
        return this.state.loading || this.state.monsters.length === 0;
    };
    Actions.prototype.removeCraftingType = function () {
        this.actionsManager.removeCraftingSection();
    };
    Actions.prototype.resetDuelData = function () {
        this.setState({
            duel_fight_info: null,
        });
    };
    Actions.prototype.getTypeOfSpecialtyGear = function () {
        if (this.state.show_hell_forged_section) {
            return "Hell Forged";
        }
        if (this.state.show_purgatory_chains_section) {
            return "Purgatory Chains";
        }
        if (this.state.show_twisted_earth_section) {
            return "Twisted Earth";
        }
        return "";
    };
    Actions.prototype.render = function () {
        if (this.isLoading()) {
            return React.createElement(ComponentLoading, null);
        }
        return (React.createElement("div", { className: "p-4" },
            React.createElement("div", { className: "grid grid-cols-1 md:grid-cols-4 gap-4" },
                React.createElement("div", { className: "md:col-span-1 space-y-4" },
                    !this.state.show_exploration &&
                        !this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight &&
                        this.props.character !== null && (React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(DropDown, { menu_items: this.actionsManager.buildCraftingList(this.openCrafting.bind(this)), button_title: "Craft/Enchant", disabled: this.actionsManager.cannotCraft(), selected_name: this.actionsManager.getSelectedCraftingOption() }))),
                    !this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight && (React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(SuccessOutlineButton, { button_label: "Exploration", on_click: this.manageExploration.bind(this), additional_css: "w-full", disabled: this.props.character.is_dead }))),
                    this.props.character.can_access_hell_forged && (React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(SuccessOutlineButton, { button_label: "Hell Forged Gear", on_click: this.manageHellForgedShop.bind(this), additional_css: "w-full", disabled: this.props.character.is_dead }))),
                    this.props.character.can_access_purgatory_chains && (React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(SuccessOutlineButton, { button_label: "Purgatory Chains Gear", on_click: this.managedPurgatoryChainsShop.bind(this), additional_css: "w-full", disabled: this.props.character.is_dead }))),
                    this.props.character.can_access_twisted_memories && (React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(SuccessOutlineButton, { button_label: "Twisted Earth Gear", on_click: this.managedTwistedEarthShop.bind(this), additional_css: "w-full", disabled: this.props.character.is_dead }))),
                    React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(SuccessOutlineButton, { button_label: "Slots", on_click: this.manageGamblingSection.bind(this), additional_css: "w-full", disabled: this.props.character.is_dead })),
                    this.props.celestial_id !== 0 &&
                        !this.state.show_exploration &&
                        !this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        this.props.can_engage_celestial && (React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(SuccessOutlineButton, { button_label: "Fight Celestial!", on_click: this.manageFightCelestial.bind(this), additional_css: "w-full", disabled: this.props.character.is_dead ||
                                this.props.character
                                    .is_automation_running ||
                                !this.props.can_engage_celestial }))),
                    this.state.characters_for_dueling.length > 0 &&
                        !this.state.show_exploration &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight && (React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(SuccessOutlineButton, { button_label: "Duel!", on_click: this.manageDuel.bind(this), additional_css: "w-full", disabled: this.props.character.is_dead ||
                                this.props.character
                                    .is_automation_running ||
                                this.props.character.killed_in_pvp }))),
                    this.props.character.can_register_for_pvp &&
                        !this.state.show_duel_fight &&
                        !this.state.show_exploration &&
                        !this.state.show_celestial_fight && (React.createElement("div", { className: "w-full md:w-3/4" },
                        React.createElement(SkyOutlineButton, { button_label: "Join PVP", on_click: this.manageJoinPvp.bind(this), additional_css: "w-full", disabled: this.props.character.is_dead })))),
                React.createElement("div", { className: "md:col-span-3 mt-1" },
                    !this.state.show_exploration &&
                        !this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight &&
                        this.state.raid_monsters.length === 0 && (React.createElement(MonsterActions, { monsters: this.state.monsters, character: this.props.character, character_statuses: this.props.character_status, is_small: false },
                        this.state.crafting_type !== null && (React.createElement(CraftingSection, { remove_crafting: this.removeCraftingType.bind(this), type: this.state.crafting_type, character_id: this.props.character.id, user_id: this.props.character.user_id, cannot_craft: this.actionsManager.cannotCraft(), fame_tasks: this.props.fame_tasks, is_small: false })),
                        (this.state.show_hell_forged_section ||
                            this.state
                                .show_purgatory_chains_section ||
                            this.state
                                .show_twisted_earth_section) && (React.createElement(Shop, { type: this.getTypeOfSpecialtyGear(), character_id: this.props.character.id, close_hell_forged: this.manageHellForgedShop.bind(this), close_purgatory_chains: this.managedPurgatoryChainsShop.bind(this), close_twisted_earth: this.managedTwistedEarthShop.bind(this) })))),
                    !this.state.show_exploration &&
                        !this.state.show_duel_fight &&
                        !this.state.show_join_pvp &&
                        !this.state.show_celestial_fight &&
                        this.state.raid_monsters.length > 0 && (React.createElement(RaidSection, { raid_monsters: this.state.raid_monsters, character_id: this.props.character.id, can_attack: this.props.character.can_attack, is_dead: this.props.character.is_dead, is_small: false, character_name: this.props.character.name, user_id: this.props.character.user_id, character_current_health: this.props.character.health },
                        this.state.crafting_type !== null && (React.createElement(CraftingSection, { remove_crafting: this.removeCraftingType.bind(this), type: this.state.crafting_type, character_id: this.props.character.id, user_id: this.props.character.user_id, cannot_craft: this.actionsManager.cannotCraft(), fame_tasks: this.props.fame_tasks })),
                        (this.state.show_hell_forged_section ||
                            this.state
                                .show_purgatory_chains_section) && (React.createElement(Shop, { type: this.state
                                .show_hell_forged_section
                                ? "Hell Forged"
                                : "Purgatory Chains", character_id: this.props.character.id, close_hell_forged: this.manageHellForgedShop.bind(this), close_purgatory_chains: this.managedPurgatoryChainsShop.bind(this) })))),
                    this.state.show_duel_fight && (React.createElement(DuelPlayer, { characters: this.state.characters_for_dueling, duel_data: this.state.duel_fight_info, character: this.props.character, manage_pvp: this.manageDuel.bind(this), reset_duel_data: this.resetDuelData.bind(this), is_small: false })),
                    this.state.show_exploration && (React.createElement(ExplorationSection, { character: this.props.character, manage_exploration: this.manageExploration.bind(this), monsters: this.state.monsters })),
                    this.state.show_celestial_fight && (React.createElement(CelestialFight, { character: this.props.character, manage_celestial_fight: this.manageFightCelestial.bind(this), celestial_id: this.props.celestial_id, update_celestial: this.props.update_celestial })),
                    this.state.show_join_pvp && (React.createElement(JoinPvp, { manage_section: this.manageJoinPvp.bind(this), character_id: this.props.character.id })),
                    this.state.show_gambling_section && (React.createElement(GamblingSection, { character: this.props.character, close_gambling_section: this.manageGamblingSection.bind(this), is_small: false })))),
            React.createElement(ActionsTimers, { user_id: this.props.character.user_id })));
    };
    return Actions;
}(React.Component));
export default Actions;
//# sourceMappingURL=actions.js.map