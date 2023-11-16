import MapSection from "../map-section";

/**
 * Fetches the player icon position for the map component.
 *
 * @param component
 */
export const playerIconPosition = (component: MapSection): {top: string, left: string} => {
    return {
        top: component.state.character_position.y + 'px',
        left: component.state.character_position.x + 'px',
    }
}

/**
 * Show the map image.
 *
 * @param component
 */
export const getStyle = (component: MapSection): { backgroundImage: string, height: number, backgroundRepeat?: string, width?: number } => {
    if (component.props.view_port >= 1600 && component.props.view_port <= 1920) {
        return {backgroundImage: `url("${component.state.map_url}")`, height: 500};
    }

    if (component.props.view_port >= 1920) {
        return {backgroundImage: `url("${component.state.map_url}")`, backgroundRepeat: 'no-repeat', height: 500};
    }

    return {backgroundImage: `url("${component.state.map_url}")`, height: 500, width: 500};
}
