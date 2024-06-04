var ManageTeleportModalState = (function () {
    function ManageTeleportModalState(component) {
        this.component = component;
    }
    ManageTeleportModalState.prototype.updateTeleportModalState = function () {
        this.setCurrentLocation();
        this.setPlayerKingdom();
        this.setEnemyKingdom();
        this.setNPCKingdom();
    };
    ManageTeleportModalState.prototype.setCurrentLocation = function () {
        var props = this.component.props;
        var state = this.component.state;
        if (props.locations !== null && state.current_location === null) {
            var foundLocation = props.locations.filter(function (location) {
                return (
                    location.x === props.character_position.x &&
                    location.y === props.character_position.y
                );
            });
            if (foundLocation.length > 0) {
                this.component.setState({
                    current_location: foundLocation[0],
                });
            }
        }
    };
    ManageTeleportModalState.prototype.setPlayerKingdom = function () {
        var props = this.component.props;
        var state = this.component.state;
        if (
            props.player_kingdoms !== null &&
            state.current_player_kingdom === null
        ) {
            var foundKingdom = props.player_kingdoms.filter(function (kingdom) {
                return (
                    kingdom.x_position === props.character_position.x &&
                    kingdom.y_position === props.character_position.y
                );
            });
            if (foundKingdom.length > 0) {
                this.component.setState({
                    current_player_kingdom: foundKingdom[0],
                });
            }
        }
    };
    ManageTeleportModalState.prototype.setEnemyKingdom = function () {
        var props = this.component.props;
        var state = this.component.state;
        if (
            props.enemy_kingdoms !== null &&
            state.current_enemy_kingdom === null
        ) {
            var foundKingdom = props.enemy_kingdoms.filter(function (kingdom) {
                return (
                    kingdom.x_position === props.character_position.x &&
                    kingdom.y_position === props.character_position.y
                );
            });
            if (foundKingdom.length > 0) {
                this.component.setState({
                    current_enemy_kingdom: foundKingdom[0],
                });
            }
        }
    };
    ManageTeleportModalState.prototype.setNPCKingdom = function () {
        var props = this.component.props;
        var state = this.component.state;
        if (props.npc_kingdoms !== null && state.current_npc_kingdom === null) {
            var foundKingdom = props.npc_kingdoms.filter(function (kingdom) {
                return (
                    kingdom.x_position === props.character_position.x &&
                    kingdom.y_position === props.character_position.y
                );
            });
            if (foundKingdom.length > 0) {
                this.component.setState({
                    current_npc_kingdom: foundKingdom[0],
                });
            }
        }
    };
    return ManageTeleportModalState;
})();
export default ManageTeleportModalState;
//# sourceMappingURL=manage-teleport-modal-state.js.map
