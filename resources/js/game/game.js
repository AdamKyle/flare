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
import clsx from "clsx";
import React from "react";
import KingdomsList from "./components/kingdoms/kingdoms-list";
import SuccessAlert from "./components/ui/alerts/simple-alerts/success-alert";
import WarningAlert from "./components/ui/alerts/simple-alerts/warning-alert";
import BasicCard from "./components/ui/cards/basic-card";
import ManualProgressBar from "./components/ui/progress-bars/manual-progress-bar";
import TabPanel from "./components/ui/tabs/tab-panel";
import Tabs from "./components/ui/tabs/tabs";
import { serviceContainer } from "./lib/containers/core-container";
import FetchGameData from "./lib/game/ajax/FetchGameData";
import GameEventListeners from "./lib/game/event-listeners/game-event-listeners";
import { removeCommas } from "./lib/game/format-number";
import CharacterSheet from "./sections/character-sheet/character-sheet";
import CharacterTopSection from "./sections/character-top-section/character-top-section";
import GameChat from "./sections/chat/game-chat";
import Quests from "./sections/components/quests/quests";
import ForceNameChange from "./sections/force-name-change/force-name-change";
import ActionSection from "./sections/game-actions-section/action-section";
import ActionTabs from "./sections/game-actions-section/action-tabs";
import ActiveBoonsActionSection from "./sections/game-actions-section/active-boons-action-section";
import GlobalTimeoutModal from "./sections/game-modals/global-timeout-modal";
import MapStateManager from "./sections/map/lib/state/map-state-manager";
import MapSection from "./sections/map/map-section";
import MapTabs from "./sections/map/map-tabs";
import ScreenRefresh from "./sections/screen-refresh/screen-refresh";
import IsTabletInPortraitDisplayAlert from "./components/ui/alerts/tablet-portrait-detector/is-tablet-in-portrait-display-alert";
var Game = (function (_super) {
    __extends(Game, _super);
    function Game(props) {
        var _this = _super.call(this, props) || this;
        _this.gameEventListener = serviceContainer().fetch(GameEventListeners);
        _this.gameEventListener.initialize(_this, _this.props.userId);
        _this.gameEventListener.registerEvents();
        _this.state = {
            view_port: 0,
            character_status: null,
            loading: true,
            finished_loading: false,
            character_currencies: null,
            secondary_loading_title: "Fetching character sheet ...",
            percentage_loaded: 0,
            celestial_id: 0,
            character: null,
            kingdoms: [],
            kingdom_logs: [],
            quests: null,
            position: null,
            disable_tabs: false,
            show_global_timeout: false,
            action_data: null,
            map_data: null,
            fame_action_tasks: null,
            show_guide_quest_completed: false,
            hide_donation_alert: false,
            tabs: [
                {
                    key: "game",
                    name: "Game",
                },
                {
                    key: "character-sheet",
                    name: "Character Sheet",
                },
                {
                    key: "quests",
                    name: "Quests",
                },
                {
                    key: "kingdoms",
                    name: "Kingdoms",
                    has_logs: false,
                },
            ],
        };
        return _this;
    }
    Game.prototype.componentDidMount = function () {
        var _this = this;
        this.setState({
            view_port: window.innerWidth || document.documentElement.clientWidth,
        });
        window.addEventListener("resize", function () {
            _this.setState({
                view_port: window.innerWidth || document.documentElement.clientWidth,
            });
        });
        new FetchGameData(this)
            .setUrls([
            {
                url: "character-sheet/" + this.props.characterId,
                name: "character-sheet",
            },
            { url: "quests/" + this.props.characterId, name: "quests" },
            {
                url: "map-actions/" + this.props.characterId,
                name: "actions",
            },
            {
                url: "player-kingdoms/" + this.props.characterId,
                name: "kingdoms",
            },
            {
                url: "map/" + this.props.characterId,
                name: "game-map",
            },
        ])
            .doAjaxCalls();
        if (this.gameEventListener) {
            this.gameEventListener.listenToEvents();
        }
        if (localStorage.getItem("hide-dontainion") !== null) {
            this.setState({
                hide_donation_alert: true,
            });
        }
    };
    Game.prototype.setStateFromData = function (data) {
        var state = MapStateManager.buildChangeState(data, this);
        this.setState({
            map_data: state,
        });
    };
    Game.prototype.updateLogIcon = function () {
        var tabs = JSON.parse(JSON.stringify(this.state.tabs));
        if (this.state.kingdom_logs.length > 0) {
            var hasLogs = this.state.kingdom_logs.filter(function (log) { return !log.opened; });
            if (hasLogs.length > 0) {
                tabs[tabs.length - 1].has_logs = true;
            }
            else {
                tabs[tabs.length - 1].has_logs = false;
            }
        }
        this.setState({
            tabs: tabs,
        });
    };
    Game.prototype.updateDisabledTabs = function () {
        this.setState({
            disable_tabs: !this.state.disable_tabs,
        });
    };
    Game.prototype.updateCharacterStatus = function (characterStatus) {
        this.setState({ character_status: characterStatus });
    };
    Game.prototype.updateCharacterCurrencies = function (currencies) {
        this.setState({ character_currencies: currencies });
    };
    Game.prototype.setCharacterPosition = function (position) {
        var character = JSON.parse(JSON.stringify(this.state.character));
        character.base_position = position;
        this.setState({
            position: position,
            character: character,
        });
    };
    Game.prototype.updateCharacterQuests = function (quests) {
        this.setState({
            quests: quests,
        });
    };
    Game.prototype.updateQuestPlane = function (plane) {
        if (this.state.quests !== null) {
            var quests = JSON.parse(JSON.stringify(this.state.quests));
            quests.player_plane = plane;
            this.setState({
                quests: quests,
            });
        }
    };
    Game.prototype.updateCelestial = function (celestialId) {
        this.setState({
            celestial_id: celestialId !== null ? celestialId : 0,
        });
    };
    Game.prototype.updateFinishedLoading = function () {
        this.setState({
            finished_loading: true,
        });
    };
    Game.prototype.setActionState = function (stateData) {
        this.setState({
            action_data: __assign(__assign({}, this.state.action_data), stateData),
        });
    };
    Game.prototype.setMapState = function (mapData) {
        this.setState({
            map_data: mapData,
        });
    };
    Game.prototype.setCanSeeFactionLoyaltyTab = function (canSee, factionId) {
        var character = JSON.parse(JSON.stringify(this.state.character));
        character.can_see_pledge_tab = canSee;
        character.pledged_to_faction_id = factionId;
        this.setState({
            character: character,
        });
    };
    Game.prototype.updateFactionActionTasks = function (fameTasks) {
        this.setState({
            fame_action_tasks: fameTasks,
        });
    };
    Game.prototype.renderLoading = function () {
        return (React.createElement("div", { className: "flex h-screen justify-center items-center max-w-md m-auto mt-[-150px]" },
            React.createElement("div", { className: "w-full" },
                React.createElement(ManualProgressBar, { label: "Loading game ...", secondary_label: this.state.secondary_loading_title, percentage_left: this.state.percentage_loaded, show_loading_icon: true }))));
    };
    Game.prototype.closeDonationAlert = function () {
        localStorage.setItem("hide-dontainion", "yes");
        this.setState({
            hide_donation_alert: true,
        });
    };
    Game.prototype.render = function () {
        if (this.state.loading) {
            return this.renderLoading();
        }
        if (this.state.quests === null) {
            return this.renderLoading();
        }
        if (this.state.character === null) {
            return this.renderLoading();
        }
        if (this.state.character_currencies === null) {
            return this.renderLoading();
        }
        if (this.state.character_status === null) {
            return this.renderLoading();
        }
        var gameMap = this.state.map_data;
        var gameMapId = null;
        if (gameMap !== null) {
            gameMapId = gameMap.game_map_id;
        }
        return (React.createElement("div", { className: "flex flex-col" },
            React.createElement("div", null,
                React.createElement(ScreenRefresh, { user_id: this.state.character.user_id }),
                React.createElement(IsTabletInPortraitDisplayAlert, null),
                React.createElement(Tabs, { tabs: this.state.tabs, disabled: this.state.disable_tabs, additonal_css: clsx({
                        "ml-[40px]": this.state.view_port >= 1024,
                    }), icon_key: "has_logs" },
                    React.createElement(TabPanel, { key: "game" },
                        React.createElement("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4" },
                            React.createElement("div", { className: "md:col-span-1" },
                                React.createElement(BasicCard, { additionalClasses: "w-full" },
                                    React.createElement(CharacterTopSection, { character: this.state.character, view_port: this.state.view_port, update_character_status: this.updateCharacterStatus.bind(this), update_character_currencies: this.updateCharacterCurrencies.bind(this) })),
                                !this.state.hide_donation_alert && (React.createElement(WarningAlert, { additional_css: "mb-4 mt-[-10px]", close_alert: this.closeDonationAlert.bind(this) })),
                                this.state.show_guide_quest_completed && (React.createElement(SuccessAlert, { additional_css: "mb-4" })),
                                React.createElement("div", { className: clsx({
                                        hidden: this.state.view_port > 932,
                                    }) },
                                    React.createElement(ActiveBoonsActionSection, { character_id: this.props.characterId })),
                                React.createElement(BasicCard, { additionalClasses: "min-h-60 mt-4" },
                                    React.createElement(ActionTabs, { use_tabs: this.state.character
                                            .can_see_pledge_tab, user_id: this.props.userId, character_id: this.props.characterId, can_attack: this.state.character.can_attack, can_craft: this.state.character.can_craft, update_faction_action_tasks: this.updateFactionActionTasks.bind(this), character_map_id: gameMapId },
                                        React.createElement(ActionSection, { character: this.state.character, character_status: this.state.character_status, character_position: this.state.position, character_currencies: this.state
                                                .character_currencies, celestial_id: this.state.celestial_id, update_celestial: this.updateCelestial.bind(this), update_plane_quests: this.updateQuestPlane.bind(this), update_character_position: this.setCharacterPosition.bind(this), view_port: this.state.view_port, can_engage_celestial: this.state.character
                                                .can_engage_celestials, action_data: this.state.action_data, map_data: this.state.map_data, update_parent_state: this.setActionState.bind(this), set_map_data: this.setMapState.bind(this), fame_tasks: this.state.fame_action_tasks })))),
                            React.createElement("div", { className: "md:col-span-1" },
                                React.createElement(BasicCard, { additionalClasses: "lg:max-h-[630px] max-w-[555px]" },
                                    React.createElement(MapTabs, { use_tabs: this.state.character
                                            .can_use_event_goals_button, character_id: this.state.character.id, user_id: this.state.character.user_id },
                                        React.createElement(MapSection, { user_id: this.props.userId, character_id: this.props.characterId, view_port: this.state.view_port, currencies: this.state
                                                .character_currencies, is_dead: this.state.character.is_dead, is_automaton_running: this.state.character
                                                .is_automation_running, can_engage_celestial: this.state.character
                                                .can_engage_celestials, automation_completed_at: this.state.character
                                                .automation_completed_at, can_engage_celestials_again_at: this.state.character
                                                .can_engage_celestials_again_at, show_celestial_fight_button: this.updateCelestial.bind(this), set_character_position: this.setCharacterPosition.bind(this), update_character_quests_plane: this.updateQuestPlane.bind(this), disable_bottom_timer: false, map_data: this.state.map_data, set_map_data: this.setMapState.bind(this) })))))),
                    React.createElement(TabPanel, { key: "character-sheet" },
                        React.createElement(CharacterSheet, { character: this.state.character, finished_loading: this.state.finished_loading, view_port: this.state.view_port, update_disable_tabs: this.updateDisabledTabs.bind(this), update_pledge_tab: this.setCanSeeFactionLoyaltyTab.bind(this), update_faction_action_tasks: this.updateFactionActionTasks.bind(this) })),
                    React.createElement(TabPanel, { key: "quests" },
                        React.createElement(BasicCard, null,
                            React.createElement(Quests, { quest_details: this.state.quests, character_id: this.props.characterId, update_quests: this.updateCharacterQuests.bind(this) }))),
                    React.createElement(TabPanel, { key: "kingdoms" },
                        React.createElement(KingdomsList, { is_dead: this.state.character_status.is_dead, my_kingdoms: this.state.kingdoms, logs: this.state.kingdom_logs, view_port: this.state.view_port, character_gold: removeCommas(this.state.character.gold), user_id: this.state.character.user_id }))),
                React.createElement(GameChat, { user_id: this.props.userId, character_id: this.state.character.id, is_silenced: this.state.character.is_silenced, can_talk_again_at: this.state.character.can_talk_again_at, is_automation_running: this.state.character.is_automation_running, is_admin: false, view_port: this.state.view_port, update_finished_loading: this.updateFinishedLoading.bind(this) }),
                this.state.character.force_name_change ? (React.createElement(ForceNameChange, { character_id: this.state.character.id })) : null,
                this.state.show_global_timeout ? (React.createElement(GlobalTimeoutModal, null)) : null)));
    };
    return Game;
}(React.Component));
export default Game;
//# sourceMappingURL=game.js.map