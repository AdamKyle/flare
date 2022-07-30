import MapActions from "../../../../sections/map/actions/map-actions";
import LocationDetails from "../types/location-details";
import PlayerKingdomsDetails from "../../types/map/player-kingdoms-details";
import NpcKingdomsDetails from "../../types/map/npc-kingdoms-details";

type LocationState =  {
    location: LocationDetails | null,
    player_kingdom_id: number |null,
    enemy_kingdom_id: number |null,
    npc_kingdom_id: number |null
}

export default class ViewLocationState {

    private component: MapActions;

    constructor(component: MapActions) {
        this.component = component;
    }

    /**
     * Update the Map Actions state with location based data.
     */
    public updateActionState() {
        this.updateViewLocationData();
    }

    /**
     * Update the view location details for the modal.
     *
     * @private
     */
    private updateViewLocationData() {
        const state = this.component.state;

        if (state.location === null && state.player_kingdom_id === null &&
            state.enemy_kingdom_id === null && state.npc_kingdom_id === null)
        {
            this.updateState();
        }

        if (state.location !== null) {
            this.handleLocationChange();
        }

        if (state.player_kingdom_id !== null) {
            this.handlePlayerKingdomChange();
        }

        if (state.enemy_kingdom_id !== null) {
            this.handleEnemyKingdomChange();
        }

        if (state.npc_kingdom_id !== null) {
            this.handleNpcKingdomsChange();
        }
    }

    /**
     * Update the player kingdom id when the player moves.
     *
     * @private
     */
    private handlePlayerKingdomChange() {
        const state = this.component.state;
        const props = this.component.props;

        if (state.player_kingdom_id === null) {
            return;
        }

        if (props.player_kingdoms === null) {
            return this.component.setState({player_kingdom_id: null});
        }

        const kingdom = props.player_kingdoms.filter((kingdom: PlayerKingdomsDetails) =>
            kingdom.x_position === props.character_position.x && kingdom.y_position === props.character_position.y);

        if (kingdom.length > 0) {
            if (kingdom[0].id !== state.player_kingdom_id) {
                return this.component.setState({player_kingdom_id: null});
            }
        } else {
            return this.component.setState({player_kingdom_id: null});
        }
    }

    /**
     * When a player moves update the location data.
     *
     * @private
     */
    private handleLocationChange() {
        const state = this.component.state;
        const props = this.component.props;

        if (state.location === null) {
            return;
        }

        if (props.locations === null) {
            return this.component.setState({ location: null });
        }

        const foundLocation = props.locations.filter((location: LocationDetails) =>
            location.x === props.character_position.x && location.y === props.character_position.y);

        if (foundLocation.length > 0) {
            if (foundLocation[0].id !== state.location.id) {
                return this.component.setState({ location: null });
            }
        } else {
            return this.component.setState({ location: null });
        }
    }

    /**
     * Handle the enemy kingdom data when the player moves.
     *
     * @private
     */
    private handleEnemyKingdomChange() {
        const state = this.component.state;
        const props = this.component.props;

        if (state.enemy_kingdom_id === 0) {
            return;
        }

        if (props.enemy_kingdoms === null) {
            return this.component.setState({ enemy_kingdom_id: null });
        }

        const foundEnemyKingdom = props.enemy_kingdoms.filter((kingdom: PlayerKingdomsDetails) =>
            kingdom.x_position === props.character_position.x && kingdom.y_position === props.character_position.y);

        if (foundEnemyKingdom.length > 0) {
            if (foundEnemyKingdom[0].id !== state.enemy_kingdom_id) {
                return this.component.setState({ enemy_kingdom_id: null });
            }
        } else {
            return this.component.setState({ enemy_kingdom_id: null });
        }
    }

    /**
     * Handle the npc kingdom data when the player moves.
     *
     * @private
     */
    private handleNpcKingdomsChange() {
        const state = this.component.state;
        const props = this.component.props;

        if (state.npc_kingdom_id === 0) {
            return;
        }

        if (props.npc_kingdoms === null) {
            return this.component.setState({ npc_kingdom_id: null });
        }

        const npcKingdom = props.npc_kingdoms.filter((kingdom: NpcKingdomsDetails) =>
            kingdom.x_position === props.character_position.x && kingdom.y_position === props.character_position.y);

        if (npcKingdom.length > 0) {
            if (npcKingdom[0].id !== state.npc_kingdom_id) {
                return this.component.setState({ npc_kingdom_id: null });
            }
        } else {
            return this.component.setState({ npc_kingdom_id: null });
        }
    }

    /**
     * Update the state with new location data for the view modal.
     *
     * @private
     */
    private updateState() {

        const props = this.component.props;

        if (props.locations == null || props.player_kingdoms === null ||
            props.enemy_kingdoms === null || props.npc_kingdoms === null)
        {
            return;
        }

        const foundLocation      = props.locations.filter((location: LocationDetails) =>
            location.x === props.character_position.x && location.y === props.character_position.y);

        const foundPlayerKingdom = props.player_kingdoms.filter((kingdom: PlayerKingdomsDetails) =>
            kingdom.x_position === props.character_position.x && kingdom.y_position === props.character_position.y);

        const foundEnemyKingdom  = props.enemy_kingdoms.filter((kingdom: PlayerKingdomsDetails) =>
            kingdom.x_position === props.character_position.x && kingdom.y_position === props.character_position.y);

        const foundNpcKingdom    = props.npc_kingdoms.filter((kingdom: NpcKingdomsDetails) =>
            kingdom.x_position === props.character_position.x && kingdom.y_position === props.character_position.y);

        let state: LocationState = {
            location: null,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
            npc_kingdom_id: null
        }

        if (foundLocation.length > 0) {
            state.location = foundLocation[0];
        }

        if (foundPlayerKingdom.length > 0) {
            state.player_kingdom_id = foundPlayerKingdom[0].id;
        }

        if (foundEnemyKingdom.length > 0) {
            state.enemy_kingdom_id = foundEnemyKingdom[0].id;
        }

        if (foundNpcKingdom.length > 0) {
            state.npc_kingdom_id = foundNpcKingdom[0].id;
        }

        if (state.location === null && state.player_kingdom_id === null && state.enemy_kingdom_id === null && state.npc_kingdom_id === null) {
            return;
        }

        let componentState = JSON.parse(JSON.stringify(this.component.state))

        componentState = {...componentState, ...state};

        this.component.setState(componentState);
    }
}
