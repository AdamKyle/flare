export var playerIconPosition = function (component) {
    return {
        top: component.state.character_position.y + "px",
        left: component.state.character_position.x + "px",
    };
};
export var getStyle = function (component) {
    if (
        component.props.view_port >= 1600 &&
        component.props.view_port <= 1920
    ) {
        return {
            backgroundImage: 'url("'.concat(component.state.map_url, '")'),
            height: 2500,
            width: 2500,
        };
    }
    if (component.props.view_port >= 1920) {
        return {
            backgroundImage: 'url("'.concat(component.state.map_url, '")'),
            backgroundRepeat: "no-repeat",
            height: 2500,
            width: 2500,
        };
    }
    return {
        backgroundImage: 'url("'.concat(component.state.map_url, '")'),
        height: 2500,
        width: 2500,
    };
};
//# sourceMappingURL=map-management.js.map
