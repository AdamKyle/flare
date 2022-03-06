import {Component} from "react";
import MapState from "./map-state";

export default class MapStateManager {

    static setState(data: any): MapState {
        return {
            map_url: data.map_url,
            map_position: {
                x: data.character_map.position_x, y: data.character_map.position_y
            },
            character_position: {
                x: data.character_map.character_position_x, y: data.character_map.character_position_y
            },
            locations: data.locations,
            player_kingdoms: data.my_kingdoms,
            enemy_kingdoms: data.other_kingdoms,
        }
    }
}
