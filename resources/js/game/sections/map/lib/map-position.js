export var getNewYPosition = function (characterY, mapPositionY, viewPort) {
    if (characterY < 288) {
        return 0;
    }
    if (characterY > 1424) {
        return -1410;
    }
    if (characterY > 1120) {
        return -1110;
    }
    if (characterY > 816) {
        return -810;
    }
    if (characterY > 513) {
        return -500;
    }
    if (characterY > 288) {
        return -200;
    }
    return mapPositionY;
};
export var getNewXPosition = function (characterX, mapPositionX, viewPort) {
    if (characterX <= 368) {
        return 0;
    }
    if (characterX > 368) {
        if (viewPort >= 1920) {
            return 0;
        }
        if (viewPort <= 1600) {
            return -50;
        }
        return 0;
    }
    return mapPositionX;
};
export var dragMap = function (position, bottomBounds, rightBounds) {
    var x = position.x,
        y = position.y;
    var yBounds = Math.sign(position.y);
    var xBounds = Math.sign(position.x);
    var bottomMapBounds = bottomBounds;
    var rightMapBounds = rightBounds;
    if (yBounds === -1) {
        bottomMapBounds += Math.abs(yBounds);
    } else {
        bottomMapBounds = 0;
    }
    if (xBounds === -1) {
        rightMapBounds += Math.abs(xBounds);
    } else {
        rightMapBounds = 0;
    }
    return {
        map_position: { x: x, y: y },
        bottom_bounds: bottomMapBounds,
        right_bounds: rightMapBounds,
    };
};
export var fetchLeftBounds = function (component) {
    if (component.props.view_port <= 1600 && component.props.view_port < 1920) {
        return -50;
    }
    return -2000;
};
//# sourceMappingURL=map-position.js.map
