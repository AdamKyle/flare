import { fetchCost } from "../lib/teleportion-costs";
var TeleportComponent = (function () {
    function TeleportComponent(component) {
        this.component = component;
    }
    TeleportComponent.prototype.getDefaultLocationValue = function () {
        var state = this.component.state;
        if (state.current_location !== null) {
            return {
                label:
                    state.current_location.name +
                    " (X/Y): " +
                    state.current_location.x +
                    "/" +
                    state.current_location.y +
                    (state.current_location.is_corrupted ? " (Corrupted)" : ""),
                value: state.current_location.id,
            };
        }
        return { value: 0, label: "" };
    };
    TeleportComponent.prototype.getDefaultPlayerKingdomValue = function () {
        var state = this.component.state;
        if (state.current_player_kingdom !== null) {
            return {
                label:
                    state.current_player_kingdom.name +
                    " (X/Y): " +
                    state.current_player_kingdom.x_position +
                    "/" +
                    state.current_player_kingdom.y_position,
                value: state.current_player_kingdom.id,
            };
        }
        return { value: 0, label: "" };
    };
    TeleportComponent.prototype.getDefaultEnemyKingdomValue = function () {
        var state = this.component.state;
        if (state.current_enemy_kingdom !== null) {
            return {
                label:
                    state.current_enemy_kingdom.name +
                    " (X/Y): " +
                    state.current_enemy_kingdom.x_position +
                    "/" +
                    state.current_enemy_kingdom.y_position,
                value: state.current_enemy_kingdom.id,
            };
        }
        return { value: 0, label: "" };
    };
    TeleportComponent.prototype.getDefaultNPCKingdomValue = function () {
        var state = this.component.state;
        if (state.current_npc_kingdom !== null) {
            return {
                label:
                    state.current_npc_kingdom.name +
                    " (X/Y): " +
                    state.current_npc_kingdom.x_position +
                    "/" +
                    state.current_npc_kingdom.y_position,
                value: state.current_npc_kingdom.id,
            };
        }
        return { value: 0, label: "" };
    };
    TeleportComponent.prototype.setSelectedXPosition = function (data) {
        var _this = this;
        this.component.setState(
            {
                x_position: data.value,
                current_location: null,
                current_player_kingdom: null,
                current_enemy_kingdom: null,
                current_npc_kingdom: null,
            },
            function () {
                var state = _this.component.state;
                var props = _this.component.props;
                var costState = fetchCost(
                    state.x_position,
                    state.y_position,
                    state.character_position,
                    props.currencies,
                );
                _this.component.setState(costState);
            },
        );
    };
    TeleportComponent.prototype.setSelectedYPosition = function (data) {
        var _this = this;
        this.component.setState(
            {
                y_position: data.value,
                current_location: null,
                current_player_kingdom: null,
            },
            function () {
                var state = _this.component.state;
                var props = _this.component.props;
                var costState = fetchCost(
                    state.x_position,
                    data.value,
                    state.character_position,
                    props.currencies,
                );
                _this.component.setState(costState);
            },
        );
    };
    TeleportComponent.prototype.setSelectedLocationData = function (data) {
        var _this = this;
        var props = this.component.props;
        if (props.locations !== null) {
            var foundLocation = props.locations.filter(function (location) {
                return location.id === data.value;
            });
            if (foundLocation.length > 0) {
                this.component.setState(
                    {
                        x_position: foundLocation[0].x,
                        y_position: foundLocation[0].y,
                        current_location: foundLocation[0],
                        current_player_kingdom: null,
                        current_enemy_kingdom: null,
                        current_npc_kingdom: null,
                    },
                    function () {
                        var state = _this.component.state;
                        var locationTeleportCost = fetchCost(
                            state.x_position,
                            state.y_position,
                            state.character_position,
                            props.currencies,
                        );
                        _this.component.setState(locationTeleportCost);
                    },
                );
            }
        }
    };
    TeleportComponent.prototype.setSelectedMyKingdomData = function (data) {
        var _this = this;
        var props = this.component.props;
        if (props.player_kingdoms !== null) {
            var foundKingdom = props.player_kingdoms.filter(
                function (location) {
                    return location.id === data.value;
                },
            );
            if (foundKingdom.length > 0) {
                this.component.setState(
                    {
                        x_position: foundKingdom[0].x_position,
                        y_position: foundKingdom[0].y_position,
                        current_player_kingdom: foundKingdom[0],
                        current_location: null,
                        current_enemy_kingdom: null,
                        current_npc_kingdom: null,
                    },
                    function () {
                        var state = _this.component.state;
                        var kingdomTeleportCosts = fetchCost(
                            state.x_position,
                            state.y_position,
                            state.character_position,
                            props.currencies,
                        );
                        _this.component.setState(kingdomTeleportCosts);
                    },
                );
            }
        }
    };
    TeleportComponent.prototype.setSelectedEnemyKingdomData = function (data) {
        var _this = this;
        var props = this.component.props;
        if (props.enemy_kingdoms !== null) {
            var foundKingdom = props.enemy_kingdoms.filter(function (location) {
                return location.id === data.value;
            });
            if (foundKingdom.length > 0) {
                this.component.setState(
                    {
                        x_position: foundKingdom[0].x_position,
                        y_position: foundKingdom[0].y_position,
                        current_player_kingdom: null,
                        current_location: null,
                        current_enemy_kingdom: foundKingdom[0],
                        current_npc_kingdom: null,
                    },
                    function () {
                        var state = _this.component.state;
                        var kingdomTeleportCosts = fetchCost(
                            state.x_position,
                            state.y_position,
                            state.character_position,
                            props.currencies,
                        );
                        _this.component.setState(kingdomTeleportCosts);
                    },
                );
            }
        }
    };
    TeleportComponent.prototype.setSelectedNPCKingdomData = function (data) {
        var _this = this;
        var props = this.component.props;
        if (props.npc_kingdoms !== null) {
            var foundKingdom = props.npc_kingdoms.filter(function (location) {
                return location.id === data.value;
            });
            if (foundKingdom.length > 0) {
                this.component.setState(
                    {
                        x_position: foundKingdom[0].x_position,
                        y_position: foundKingdom[0].y_position,
                        current_player_kingdom: null,
                        current_location: null,
                        current_enemy_kingdom: null,
                        current_npc_kingdom: foundKingdom[0],
                    },
                    function () {
                        var state = _this.component.state;
                        var kingdomTeleportCosts = fetchCost(
                            state.x_position,
                            state.y_position,
                            state.character_position,
                            props.currencies,
                        );
                        _this.component.setState(kingdomTeleportCosts);
                    },
                );
            }
        }
    };
    TeleportComponent.prototype.buildCoordinatesOptions = function (data) {
        return data.map(function (d) {
            return { label: d.toString(), value: d };
        });
    };
    TeleportComponent.prototype.buildLocationOptions = function () {
        var props = this.component.props;
        if (props.locations !== null) {
            return props.locations.map(function (location) {
                return {
                    label:
                        location.name +
                        " (X/Y): " +
                        location.x +
                        "/" +
                        location.y +
                        (location.is_corrupted ? " (Corrupted)" : ""),
                    value: location.id,
                };
            });
        }
        return [];
    };
    TeleportComponent.prototype.buildMyKingdomsOptions = function () {
        var props = this.component.props;
        if (props.player_kingdoms !== null) {
            return props.player_kingdoms.map(function (kingdom) {
                return {
                    label:
                        kingdom.name +
                        " (X/Y): " +
                        kingdom.x_position +
                        "/" +
                        kingdom.y_position,
                    value: kingdom.id,
                };
            });
        }
        return [];
    };
    TeleportComponent.prototype.buildEnemyKingdomOptions = function () {
        var props = this.component.props;
        if (props.enemy_kingdoms !== null) {
            return props.enemy_kingdoms.map(function (kingdom) {
                return {
                    label:
                        kingdom.name +
                        " (X/Y): " +
                        kingdom.x_position +
                        "/" +
                        kingdom.y_position,
                    value: kingdom.id,
                };
            });
        }
        return [];
    };
    TeleportComponent.prototype.buildNpcKingdomOptions = function () {
        var props = this.component.props;
        if (props.npc_kingdoms !== null) {
            return props.npc_kingdoms.map(function (kingdom) {
                return {
                    label:
                        kingdom.name +
                        " (X/Y): " +
                        kingdom.x_position +
                        "/" +
                        kingdom.y_position,
                    value: kingdom.id,
                };
            });
        }
        return [];
    };
    return TeleportComponent;
})();
export default TeleportComponent;
//# sourceMappingURL=teleport-component.js.map
