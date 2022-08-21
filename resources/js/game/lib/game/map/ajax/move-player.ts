import {Component} from "react";
import {movePlayer} from "../move-player";
import {generateServerMessage} from "../../../ajax/generate-server-message";
import Ajax from "../../../ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import MapStateManager from "../state/map-state-manager";
import {getPortLocation} from "../location-helpers";
import {getNewXPosition, getNewYPosition} from "../map-position";
import DirectionalMovement from "../../../../sections/map/actions/directional-movement";
import MapActions from "../../../../sections/map/actions/map-actions";
import MapData from "../request-types/MapData";

export default class MovePlayer {

    private component: Component;

    private characterPosition: {x: number, y: number} | null;

    private mapPosition: {x: number, y: number} | null;

    constructor(component: Component) {
        this.component = component;

        this.characterPosition = null;

        this.mapPosition = null;
    }

    setCharacterPosition(characterPosition: {x: number, y: number}): MovePlayer {
        this.characterPosition = characterPosition;

        return this;
    }

    setMapPosition(mapPosition: {x: number, y: number}): MovePlayer {
        this.mapPosition = mapPosition;

        return this;
    }

    movePlayer(characterId: number, direction: string, component: DirectionalMovement) {

        if (this.characterPosition === null || this.mapPosition === null) {

            this.component.setState({
                can_player_move: true
            });

            return generateServerMessage('cant_move');
        }

        const playerPosition = movePlayer(this.characterPosition.x, this.characterPosition.y, direction);

        if (!playerPosition) {
            return generateServerMessage('cant_move');
        }

        (new Ajax()).setRoute('move/' + characterId).setParameters({
            position_x: this.mapPosition.x,
            position_y: this.mapPosition.y,
            character_position_x: playerPosition.x,
            character_position_y: playerPosition.y,
        }).doAjaxCall('post', (result: AxiosResponse) => {
            component.props.update_map_state(result.data);
        }, (error: AxiosError) => {
            this.handleErrors(error);
        })
    }

    teleportPlayer(data: {x: number, y: number, cost: number, timeout: number},
                   characterId: number,
                   updateMapState: (data: MapData, callback?: () => void) => void)
    {
        (new Ajax()).setRoute('map/teleport/' + characterId).setParameters(data)
            .doAjaxCall('post', (result: AxiosResponse) => {
                updateMapState(result.data);
            }, (error: AxiosError) => {
                this.handleErrors(error);
            });
    }

    setSail(data: {x: number, y: number, cost: number, timeout: number},
            characterId: number, viewPort: number,
            updateMapState: (data: MapData, callback?: () => void) => void
        ) {
        (new Ajax()).setRoute('map/set-sail/' + characterId).setParameters(data)
            .doAjaxCall('post', (result: AxiosResponse) => {
                updateMapState(result.data);
            }, (error: AxiosError) => {
                this.handleErrors(error);
            });
    }

    handleErrors(error: AxiosError) {
        const response = error.response;

        if (typeof response === 'undefined') {
            return;
        }

        if (response.status === 401) {
            return location.reload();
        }
    }
}

