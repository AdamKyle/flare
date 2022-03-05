/**
 * Gets the new Y Position of the map.
 *
 * @param characterY
 * @param mapPositionY
 * @type [{characterY: number, mapPositionY: number}]
 */
export const getNewYPosition = (characterY: number, mapPositionY: number): number => {
    if (characterY < 320) {
        return 0;
    }

    if (characterY > 320) {
        return -150;
    }

    return mapPositionY;
}

/**
 * Get the new X position of the map.
 *
 * @param characterX
 * @param mapPositionX
 * @type [{characterX: number, mapPositionX: number}]
 */
export const getNewXPosition = (characterX: number, mapPositionX: number): number => {
    if (characterX <= 368) {
        return 0;
    }

    if (characterX > 368) {
        return -100;
    }

    return mapPositionX;
}

/**
 * Returns new position of the map as it is dragged.
 *
 * @param position
 * @param bottomBounds
 * @param rightBounds
 * @type [{position: {x: number, y: number}, bottomBounds: number, rightBounds: number}]
 */
export const dragMap = (position: {x: number, y: number}, bottomBounds: number, rightBounds: number): object => {
    const {x, y} = position;
    const yBounds = Math.sign(position.y);
    const xBounds = Math.sign(position.x);
    let bottomMapBounds = bottomBounds;
    let rightMapBounds = rightBounds;

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
        map_position: {x, y},
        bottom_bounds: bottomMapBounds,
        right_bounds: rightMapBounds,
    }
}
