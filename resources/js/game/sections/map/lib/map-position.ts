/**
 * Gets the new Y Position of the map.
 *
 * @param characterY
 * @param mapPositionY
 * @type [{characterY: number, mapPositionY: number}]
 */
import MapSection from "../map-section";

export const getNewYPosition = (characterY: number, mapPositionY: number, viewPort: number): number => {
    if (characterY < 288) {
        return 0;
    }

    if (characterY > 288) {
        return -200;
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
export const getNewXPosition = (characterX: number, mapPositionX: number, viewPort: number): number => {
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

/**
 * Uses the map component props to fetch the bound sof the map.
 *
 * @param component
 */
export const fetchLeftBounds = (component: MapSection): number => {

    if (component.props.view_port <= 1600 && component.props.view_port < 1920) {
        return -50;
    }

    return 0;
}
