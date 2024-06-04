import { capitalize } from "lodash";
import Ajax from "../../ajax/ajax";
import { DateTime } from "luxon";
var SmallActionsManager = (function () {
    function SmallActionsManager(component) {
        this.component = component;
    }
    SmallActionsManager.prototype.initialFetch = function () {
        var _this = this;
        var props = this.component.props;
        new Ajax().setRoute("map-actions/" + props.character.id).doAjaxCall(
            "get",
            function (result) {
                _this.component.setState({
                    monsters: result.data.monsters,
                    attack_time_out:
                        props.character.can_attack_again_at !== null
                            ? _this.calculateTimeLeft(
                                  props.character.can_attack_again_at,
                              )
                            : 0,
                    crafting_time_out:
                        props.character.can_craft_again_at !== null
                            ? _this.calculateTimeLeft(
                                  props.character.can_craft_again_at,
                              )
                            : 0,
                    automation_time_out:
                        props.character.automation_completed_at !== null
                            ? props.character.automation_completed_at
                            : 0,
                    celestial_time_out:
                        props.character.can_engage_celestials_again_at !== null
                            ? props.character.can_engage_celestials_again_at
                            : 0,
                    loading: false,
                });
            },
            function (error) {
                console.error(error);
            },
        );
    };
    SmallActionsManager.prototype.setCharactersForDueling = function (
        eventCharactersForDueling,
    ) {
        var charactersForDueling = [];
        var props = this.component.props;
        if (eventCharactersForDueling.length === 0) {
            return;
        }
        if (props.character_position !== null) {
            charactersForDueling = eventCharactersForDueling.filter(
                function (character) {
                    if (props.character_position !== null) {
                        if (character.id !== props.character.id) {
                            return character;
                        }
                    }
                },
            );
            if (charactersForDueling.length === 0) {
                return;
            }
            this.component.setState({
                characters_for_dueling: charactersForDueling,
            });
        }
    };
    SmallActionsManager.prototype.calculateTimeLeft = function (timeLeft) {
        var future = DateTime.fromISO(timeLeft);
        var now = DateTime.now();
        var diff = future.diff(now, ["seconds"]);
        var objectDiff = diff.toObject();
        if (typeof objectDiff.seconds === "undefined") {
            return 0;
        }
        return parseInt(objectDiff.seconds.toFixed(0));
    };
    SmallActionsManager.prototype.setSelectedAction = function (data) {
        var _this = this;
        this.component.setState(
            {
                selected_action: data.value,
            },
            function () {
                if (data.value === "pvp-fight") {
                    _this.component.setState({
                        show_duel_fight: true,
                    });
                }
            },
        );
    };
    SmallActionsManager.prototype.setDuelCharacters = function () {
        var state = this.component.state;
        var props = this.component.props;
        if (typeof state.characters_for_dueling !== "undefined") {
            var characters = state.characters_for_dueling.filter(
                function (character) {
                    var _a, _b, _c;
                    return (
                        character.character_position_x ===
                            ((_a = props.character_position) === null ||
                            _a === void 0
                                ? void 0
                                : _a.x) &&
                        character.character_position_y ===
                            ((_b = props.character_position) === null ||
                            _b === void 0
                                ? void 0
                                : _b.y) &&
                        character.game_map_id ===
                            ((_c = props.character_position) === null ||
                            _c === void 0
                                ? void 0
                                : _c.game_map_id) &&
                        character.name !== props.character.name
                    );
                },
            );
            this.component.setState({
                characters_for_dueling: characters,
            });
        }
    };
    SmallActionsManager.prototype.buildOptions = function () {
        var props = this.component.props;
        var state = this.component.state;
        var options = [
            {
                label: "Exploration",
                value: "explore",
            },
            {
                label: "Craft",
                value: "craft",
            },
        ];
        if (!props.character.is_automation_running) {
            options.unshift({
                label: "Fight",
                value: "fight",
            });
        }
        options.push({
            label: "Slots",
            value: "slots",
        });
        options.push({
            label: "Map Movement",
            value: "map-movement",
        });
        if (
            state.characters_for_dueling.length > 0 &&
            !props.character.killed_in_pvp
        ) {
            options.push({
                label: "Pvp Fight",
                value: "pvp-fight",
            });
        }
        if (
            props.celestial_id !== 0 &&
            props.celestial_id !== null &&
            props.character.can_engage_celestials
        ) {
            options.push({
                label: "Celestial Fight",
                value: "celestial-fight",
            });
        }
        if (props.character.can_register_for_pvp) {
            options.push({
                label: "Join Monthly PVP",
                value: "join-monthly-pvp",
            });
        }
        if (props.character.can_access_hell_forged) {
            options.push({
                label: "Hell Forged Gear",
                value: "hell-forged-gear",
            });
        }
        if (props.character.can_access_purgatory_chains) {
            options.push({
                label: "Purgatory Chains Gear",
                value: "purgatory-chains-gear",
            });
        }
        if (props.character.can_access_twisted_memories) {
            options.push({
                label: "Twisted Earth",
                value: "twisted-earth-gear",
            });
        }
        return options;
    };
    SmallActionsManager.prototype.defaultSelectedAction = function () {
        var state = this.component.state;
        if (
            typeof state.selected_action !== "undefined" &&
            state.selected_action !== null
        ) {
            return [
                {
                    label: capitalize(state.selected_action),
                    value: state.selected_action,
                },
            ];
        }
        return [
            {
                label: "Please Select Action",
                value: "",
            },
        ];
    };
    return SmallActionsManager;
})();
export default SmallActionsManager;
//# sourceMappingURL=small-actions-manager.js.map
