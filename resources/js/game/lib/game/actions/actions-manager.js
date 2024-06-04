import { capitalize } from "lodash";
import { DateTime } from "luxon";
var ActionsManager = (function () {
    function ActionsManager(component) {
        this.component = component;
    }
    ActionsManager.prototype.updateStateOnComponentUpdate = function () {
        this.setCraftingTypeOnUpdate();
        this.setDuelingStateOnUpdate();
    };
    ActionsManager.prototype.setCharactersForDueling = function (
        eventCharactersForDueling,
    ) {
        var charactersForDueling = [];
        var props = this.component.props;
        if (
            props.character_position !== null &&
            typeof props.character.base_position !== "undefined"
        ) {
            charactersForDueling = eventCharactersForDueling.filter(
                function (character) {
                    if (
                        character.id !== props.character.id &&
                        character.character_position_x ===
                            props.character.base_position.x &&
                        character.character_position_y ===
                            props.character.base_position.y
                    ) {
                        return character;
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
    ActionsManager.prototype.calculateTimeLeft = function (timeLeft) {
        var future = DateTime.fromISO(timeLeft);
        var now = DateTime.now();
        var diff = future.diff(now, ["seconds"]);
        var objectDiff = diff.toObject();
        if (typeof objectDiff.seconds === "undefined") {
            return 0;
        }
        return parseInt(objectDiff.seconds.toFixed(0));
    };
    ActionsManager.prototype.setCraftingTypeOnUpdate = function () {
        var _a;
        var state = this.component.state;
        var props = this.component.props;
        if (state.crafting_type !== null) {
            if (
                state.crafting_type === "queen" &&
                !props.character.can_access_queen
            ) {
                this.component.setState({ crafting_type: null });
            }
            if (
                state.crafting_type === "workbench" &&
                !props.character.can_use_work_bench
            ) {
                this.component.setState({ crafting_type: null });
            }
            if (
                state.crafting_type === "labyrinth-oracle" &&
                !((_a = props.character) === null || _a === void 0
                    ? void 0
                    : _a.can_access_labyrinth_oracle)
            ) {
                this.component.setState({ crafting_type: null });
            }
        }
    };
    ActionsManager.prototype.setDuelingStateOnUpdate = function () {
        var props = this.component.props;
        var state = this.component.state;
        if (
            props.character_position !== null &&
            state.characters_for_dueling.length > 0 &&
            state.characters_for_dueling.length == 0
        ) {
            if (typeof props.character_position.game_map_id === "undefined") {
                return;
            }
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
                                : _c.game_map_id)
                    );
                },
            );
            if (characters.length === 0) {
                return;
            }
            this.component.setState({
                characters_for_dueling: characters,
            });
        }
    };
    ActionsManager.prototype.setCraftingType = function (type) {
        this.component.setState({
            crafting_type: type,
        });
    };
    ActionsManager.prototype.removeCraftingSection = function () {
        this.component.setState({
            crafting_type: null,
        });
    };
    ActionsManager.prototype.updateCraftingTimer = function () {
        this.component.setState({
            crafting_time_out: 0,
        });
    };
    ActionsManager.prototype.getSelectedCraftingOption = function () {
        if (this.component.state.crafting_type !== null) {
            return capitalize(this.component.state.crafting_type);
        }
        return "";
    };
    ActionsManager.prototype.cannotCraft = function () {
        var state = this.component.state;
        var props = this.component.props;
        return (
            state.crafting_time_out > 0 ||
            !props.character_status.can_craft ||
            props.character_status.is_dead
        );
    };
    ActionsManager.prototype.buildCraftingList = function (handler) {
        var options = [
            {
                name: "Craft",
                icon_class: "ra ra-hammer",
                on_click: function () {
                    return handler("craft");
                },
            },
            {
                name: "Enchant",
                icon_class: "ra ra-burning-embers",
                on_click: function () {
                    return handler("enchant");
                },
            },
            {
                name: "Trinketry",
                icon_class: "ra ra-anvil",
                on_click: function () {
                    return handler("trinketry");
                },
            },
            {
                name: "Gem Crafting",
                icon_class: "fas fa-gem",
                on_click: function () {
                    return handler("gem-crafting");
                },
            },
        ];
        if (!this.component.props.character.is_alchemy_locked) {
            options.splice(2, 0, {
                name: "Alchemy",
                icon_class: "ra ra-potion",
                on_click: function () {
                    return handler("alchemy");
                },
            });
        }
        if (this.component.props.character.can_use_work_bench) {
            if (typeof options[2] !== "undefined") {
                options.splice(3, 0, {
                    name: "Workbench",
                    icon_class: "ra ra-brandy-bottle",
                    on_click: function () {
                        return handler("workbench");
                    },
                });
            } else {
                options.splice(2, 0, {
                    name: "Workbench",
                    icon_class: "ra ra-brandy-bottle",
                    on_click: function () {
                        return handler("workbench");
                    },
                });
            }
        }
        if (this.component.props.character.can_access_purgatory_chains) {
            if (typeof options[3] !== "undefined") {
                options.splice(4, 0, {
                    name: "Seer Camp",
                    icon_class: "fas fa-campground",
                    on_click: function () {
                        return handler("seer-camp");
                    },
                });
            } else {
                options.splice(3, 0, {
                    name: "Seer Camp",
                    icon_class: "fas fa-campground",
                    on_click: function () {
                        return handler("seer-camp");
                    },
                });
            }
        }
        if (this.component.props.character.can_access_labyrinth_oracle) {
            if (typeof options[3] !== "undefined") {
                options.splice(4, 0, {
                    name: "Labyrinth Oracle",
                    icon_class: "ra ra-crystal-ball",
                    on_click: function () {
                        return handler("labyrinth-oracle");
                    },
                });
            } else {
                options.splice(4, 0, {
                    name: "Labyrinth Oracle",
                    icon_class: "ra ra-crystal-ball",
                    on_click: function () {
                        return handler("labyrinth-oracle");
                    },
                });
            }
        }
        if (this.component.props.character.can_access_queen) {
            if (typeof options[2] !== "undefined") {
                options.splice(3, 0, {
                    name: "Queen of Hearts",
                    icon_class: "ra  ra-hearts",
                    on_click: function () {
                        return handler("queen");
                    },
                });
            } else {
                options.splice(2, 0, {
                    name: "Queen of Hearts",
                    icon_class: "ra ra-hearts",
                    on_click: function () {
                        return handler("queen");
                    },
                });
            }
        }
        return options;
    };
    return ActionsManager;
})();
export default ActionsManager;
//# sourceMappingURL=actions-manager.js.map
