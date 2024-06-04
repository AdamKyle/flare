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
var ViewLocationState = (function () {
    function ViewLocationState(component) {
        this.component = component;
    }
    ViewLocationState.prototype.updateActionState = function () {
        this.updateViewLocationData();
    };
    ViewLocationState.prototype.updateViewLocationData = function () {
        var state = this.component.state;
        if (
            state.location === null &&
            state.player_kingdom_id === null &&
            state.enemy_kingdom_id === null &&
            state.npc_kingdom_id === null
        ) {
            this.updateState();
        }
        if (state.location !== null) {
            this.handleLocationChange();
        }
        if (state.player_kingdom_id !== null) {
            this.handlePlayerKingdomChange();
        }
        if (state.enemy_kingdom_id !== null) {
            this.handleEnemyKingdomChange();
        }
        if (state.npc_kingdom_id !== null) {
            this.handleNpcKingdomsChange();
        }
    };
    ViewLocationState.prototype.handlePlayerKingdomChange = function () {
        var state = this.component.state;
        var props = this.component.props;
        if (state.player_kingdom_id === null) {
            return;
        }
        if (props.player_kingdoms === null) {
            return this.component.setState({ player_kingdom_id: null });
        }
        var kingdom = props.player_kingdoms.filter(function (kingdom) {
            return (
                kingdom.x_position === props.character_position.x &&
                kingdom.y_position === props.character_position.y
            );
        });
        if (kingdom.length > 0) {
            if (kingdom[0].id !== state.player_kingdom_id) {
                return this.component.setState({ player_kingdom_id: null });
            }
        } else {
            return this.component.setState({ player_kingdom_id: null });
        }
    };
    ViewLocationState.prototype.handleLocationChange = function () {
        var state = this.component.state;
        var props = this.component.props;
        if (state.location === null) {
            return;
        }
        if (props.locations === null) {
            return this.component.setState({ location: null });
        }
        var foundLocation = props.locations.filter(function (location) {
            return (
                location.x === props.character_position.x &&
                location.y === props.character_position.y
            );
        });
        if (foundLocation.length > 0) {
            if (foundLocation[0].id !== state.location.id) {
                return this.component.setState({ location: null });
            }
        } else {
            return this.component.setState({ location: null });
        }
    };
    ViewLocationState.prototype.handleEnemyKingdomChange = function () {
        var state = this.component.state;
        var props = this.component.props;
        if (state.enemy_kingdom_id === 0) {
            return;
        }
        if (props.enemy_kingdoms === null) {
            return this.component.setState({ enemy_kingdom_id: null });
        }
        var foundEnemyKingdom = props.enemy_kingdoms.filter(function (kingdom) {
            return (
                kingdom.x_position === props.character_position.x &&
                kingdom.y_position === props.character_position.y
            );
        });
        if (foundEnemyKingdom.length > 0) {
            if (foundEnemyKingdom[0].id !== state.enemy_kingdom_id) {
                return this.component.setState({ enemy_kingdom_id: null });
            }
        } else {
            return this.component.setState({ enemy_kingdom_id: null });
        }
    };
    ViewLocationState.prototype.handleNpcKingdomsChange = function () {
        var state = this.component.state;
        var props = this.component.props;
        if (state.npc_kingdom_id === 0) {
            return;
        }
        if (props.npc_kingdoms === null) {
            return this.component.setState({ npc_kingdom_id: null });
        }
        var npcKingdom = props.npc_kingdoms.filter(function (kingdom) {
            return (
                kingdom.x_position === props.character_position.x &&
                kingdom.y_position === props.character_position.y
            );
        });
        if (npcKingdom.length > 0) {
            if (npcKingdom[0].id !== state.npc_kingdom_id) {
                return this.component.setState({ npc_kingdom_id: null });
            }
        } else {
            return this.component.setState({ npc_kingdom_id: null });
        }
    };
    ViewLocationState.prototype.updateState = function () {
        var props = this.component.props;
        if (
            props.locations == null ||
            props.player_kingdoms === null ||
            props.enemy_kingdoms === null ||
            props.npc_kingdoms === null
        ) {
            return;
        }
        var foundLocation = props.locations.filter(function (location) {
            return (
                location.x === props.character_position.x &&
                location.y === props.character_position.y
            );
        });
        var foundPlayerKingdom = props.player_kingdoms.filter(
            function (kingdom) {
                return (
                    kingdom.x_position === props.character_position.x &&
                    kingdom.y_position === props.character_position.y
                );
            },
        );
        var foundEnemyKingdom = props.enemy_kingdoms.filter(function (kingdom) {
            return (
                kingdom.x_position === props.character_position.x &&
                kingdom.y_position === props.character_position.y
            );
        });
        var foundNpcKingdom = props.npc_kingdoms.filter(function (kingdom) {
            return (
                kingdom.x_position === props.character_position.x &&
                kingdom.y_position === props.character_position.y
            );
        });
        var state = {
            location: null,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
            npc_kingdom_id: null,
        };
        if (foundLocation.length > 0) {
            state.location = foundLocation[0];
        }
        if (foundPlayerKingdom.length > 0) {
            state.player_kingdom_id = foundPlayerKingdom[0].id;
        }
        if (foundEnemyKingdom.length > 0) {
            state.enemy_kingdom_id = foundEnemyKingdom[0].id;
        }
        if (foundNpcKingdom.length > 0) {
            state.npc_kingdom_id = foundNpcKingdom[0].id;
        }
        if (
            state.location === null &&
            state.player_kingdom_id === null &&
            state.enemy_kingdom_id === null &&
            state.npc_kingdom_id === null
        ) {
            return;
        }
        var componentState = JSON.parse(JSON.stringify(this.component.state));
        componentState = __assign(__assign({}, componentState), state);
        this.component.setState(componentState);
    };
    return ViewLocationState;
})();
export default ViewLocationState;
//# sourceMappingURL=view-location-state.js.map
