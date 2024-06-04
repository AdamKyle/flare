export var getPortLocation = function (mapState) {
    if (mapState.locations === null) {
        return null;
    }
    var portLocation = mapState.locations.filter(function (location) {
        return (
            location.x === mapState.character_position.x &&
            location.y === mapState.character_position.y &&
            location.is_port
        );
    });
    if (portLocation.length > 0) {
        return portLocation[0];
    }
    return null;
};
export var canSettleHere = function (component) {
    var locations = [];
    if (component.props.locations !== null) {
        locations = component.props.locations.filter(function (location) {
            return (
                location.x === component.props.character_position.x &&
                location.y === component.props.character_position.y
            );
        });
    }
    var playerKingdom = component.props.player_kingdoms.filter(
        function (playerKingdom) {
            return (
                playerKingdom.x_position ===
                    component.props.character_position.x &&
                playerKingdom.y_position ===
                    component.props.character_position.y
            );
        },
    );
    var enemyKingdoms = component.props.enemy_kingdoms.filter(
        function (enemyKingdom) {
            return (
                enemyKingdom.x_position ===
                    component.props.character_position.x &&
                enemyKingdom.y_position === component.props.character_position.y
            );
        },
    );
    var npcKingdoms = component.props.npc_kingdoms.filter(
        function (npcKingdom) {
            return (
                npcKingdom.x_position ===
                    component.props.character_position.x &&
                npcKingdom.y_position === component.props.character_position.y
            );
        },
    );
    return (
        locations.length === 0 &&
        playerKingdom.length === 0 &&
        enemyKingdoms.length === 0 &&
        npcKingdoms.length === 0
    );
};
//# sourceMappingURL=location-helpers.js.map
