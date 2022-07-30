import GameMapDetails from "./game-map-details";

export default interface CharacterMapDetails {
    character_id:  number;

    character_position_x: number;

    character_position_y: number;

    game_map: GameMapDetails;

    game_map_id: number;

    id: number;

    position_x: number;

    position_y: number;

}
