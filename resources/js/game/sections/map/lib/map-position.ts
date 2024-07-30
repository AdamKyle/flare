/**
 * Gets the new Y Position of the map.
 *
 * @param characterY
 * @param mapPositionY
 * @type [{characterY: number, mapPositionY: number}]
 */
import MapSection from "../map-section";

export const getNewYPosition = (
    characterY: number,
    mapPositionY: number,
): number => {
    if (characterY <= 304) {
        return 0;
    }

    if (characterY > 2416) {
        return -2160;
    }

    if (characterY > 2112) {
        return -2100;
    }

    if (characterY > 1808) {
        return -1800;
    }

    if (characterY > 1504) {
        return -1500;
    }

    if (characterY > 1216) {
        return -1200;
    }

    if (characterY > 912) {
        return -910;
    }

    if (characterY > 608) {
        return -600;
    }

    if (characterY > 304) {
        return -300;
    }

    return mapPositionY;
};

/**
 * Get the new X position of the map.
 *
 * @param characterX
 * @param mapPositionX
 * @type [{characterX: number, mapPositionX: number}]
 */
export const getNewXPosition = (
    characterX: number,
    mapPositionX: number,
): number => {
    if (characterX <= 496) {
        return 0;
    }

    if (characterX > 2000) {
        return -2010;
    }

    if (characterX > 1500) {
        return -1510;
    }

    if (characterX > 1008) {
        return -1010;
    }

    if (characterX > 496) {
        return -510;
    }

    return mapPositionX;
};

/**
 * Returns new position of the map as it is dragged.
 *
 * @param position
 * @param bottomBounds
 * @param rightBounds
 * @type [{position: {x: number, y: number}, bottomBounds: number, rightBounds: number}]
 */
export const dragMap = (
    position: { x: number; y: number },
    bottomBounds: number,
    rightBounds: number,
): object => {
    const { x, y } = position;
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
        map_position: { x, y },
        bottom_bounds: bottomMapBounds,
        right_bounds: rightMapBounds,
    };
};

/**
 * Uses the map component props to fetch the bound sof the map.
 *
 * @param component
 */
export const fetchLeftBounds = (component: MapSection): number => {
    return -2000;
};
