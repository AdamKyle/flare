import TeleportModal from "../../../../sections/components/map-actions/modals/teleport-modal";
import {fetchCost} from "../teleportion-costs";
import LocationDetails from "../types/location-details";
import PlayerKingdomsDetails from "../../types/map/player-kingdoms-details";
import NpcKingdomsDetails from "../../types/map/npc-kingdoms-details";

type SelectedData = {
    label: string;
    value: number;
}

export default class TeleportComponent {

    private component: TeleportModal;

    constructor(component: TeleportModal) {
        this.component = component;
    }

    /**
     * Get the default location value for the modal location select.
     */
    getDefaultLocationValue(): SelectedData {
        const state = this.component.state;

        if (state.current_location !== null) {
            return {
                label: state.current_location.name + ' (X/Y): ' +
                    state.current_location.x + '/' +
                    state.current_location.y +  
                    (state.current_location.is_corrupted ? ' (Corrupted)' : ''),
                value: state.current_location.id
            }
        }

        return  {value: 0, label: ''};
    }

    /**
     * Get the default player kingdom value for the modal location select.
     */
    getDefaultPlayerKingdomValue(): SelectedData {
        const state = this.component.state;

        if (state.current_player_kingdom !== null) {
            return {
                label: state.current_player_kingdom.name + ' (X/Y): ' +
                    state.current_player_kingdom.x_position + '/' +
                    state.current_player_kingdom.y_position,
                value: state.current_player_kingdom.id
            }
        }

        return  {value: 0, label: ''};
    }

    /**
     * Get the default enemy kingdom value for the modal location select.
     */
    getDefaultEnemyKingdomValue(): SelectedData {
        const state = this.component.state;

        if (state.current_enemy_kingdom !== null) {
            return {
                label: state.current_enemy_kingdom.name + ' (X/Y): ' +
                    state.current_enemy_kingdom.x_position + '/' +
                    state.current_enemy_kingdom.y_position,
                value: state.current_enemy_kingdom.id
            }
        }

        return  {value: 0, label: ''};
    }

    /**
     * Get the default npc kingdom value for the modal location select.
     */
    getDefaultNPCKingdomValue(): SelectedData {
        const state = this.component.state;

        if (state.current_npc_kingdom !== null) {
            return {label: state.current_npc_kingdom.name + ' (X/Y): ' +
                    state.current_npc_kingdom.x_position + '/' +
                    state.current_npc_kingdom.y_position,
                value: state.current_npc_kingdom.id}
        }

        return  {value: 0, label: ''};
    }

    /**
     * Set the selected X position, and it's associated cost.
     *
     * @param data
     */
    setSelectedXPosition(data: SelectedData) {
        this.component.setState({
            x_position: data.value,
            current_location: null,
            current_player_kingdom: null,
            current_enemy_kingdom: null,
            current_npc_kingdom: null,
        }, () => {
            const state = this.component.state;
            const props = this.component.props;

            const costState = fetchCost(state.x_position, state.y_position, state.character_position, props.currencies);

            this.component.setState(costState);
        });
    }

    /**
     * Set the selected Y position, and it's associated cost.
     *
     * @param data
     */
    setSelectedYPosition (data: SelectedData) {
        this.component.setState({
            y_position: data.value,
            current_location: null,
            current_player_kingdom: null,
        }, () => {
            const state = this.component.state;
            const props = this.component.props;

            const costState = fetchCost(state.x_position,  data.value, state.character_position, props.currencies);

            this.component.setState(costState);
        });
    }

    /**
     * Set the selected location data, and it's associated cost.
     *
     * @param data
     */
    setSelectedLocationData(data: SelectedData) {
        const props = this.component.props;

        if (props.locations !== null) {
            const foundLocation = props.locations.filter((location: LocationDetails) => location.id === data.value);

            if (foundLocation.length > 0) {
                this.component.setState({
                    x_position: foundLocation[0].x,
                    y_position: foundLocation[0].y,
                    current_location: foundLocation[0],
                    current_player_kingdom: null,
                    current_enemy_kingdom: null,
                    current_npc_kingdom: null,
                }, () => {
                    const state = this.component.state;

                    const locationTeleportCost = fetchCost(state.x_position, state.y_position, state.character_position, props.currencies);

                    this.component.setState(locationTeleportCost);
                });
            }
        }
    }

    /**
     * Set the selected my kingdom data, and it's associated cost.
     *
     * @param data
     */
    setSelectedMyKingdomData(data: SelectedData) {
        const props = this.component.props;

        if (props.player_kingdoms !== null) {
            const foundKingdom = props.player_kingdoms.filter((location: PlayerKingdomsDetails) => location.id === data.value);

            if (foundKingdom.length > 0) {
                this.component.setState({
                    x_position: foundKingdom[0].x_position,
                    y_position: foundKingdom[0].y_position,
                    current_player_kingdom: foundKingdom[0],
                    current_location: null,
                    current_enemy_kingdom: null,
                    current_npc_kingdom: null,
                }, () => {
                    const state = this.component.state;

                    const kingdomTeleportCosts = fetchCost(state.x_position, state.y_position, state.character_position, props.currencies);

                    this.component.setState(kingdomTeleportCosts);
                });
            }
        }
    }

    /**
     * Set the selected enemy kingdom data, and it's associated cost.
     *
     * @param data
     */
    setSelectedEnemyKingdomData(data: SelectedData) {
        const props = this.component.props;

        if (props.enemy_kingdoms !== null) {
            const foundKingdom = props.enemy_kingdoms.filter((location: PlayerKingdomsDetails) => location.id === data.value);

            if (foundKingdom.length > 0) {
                this.component.setState({
                    x_position: foundKingdom[0].x_position,
                    y_position: foundKingdom[0].y_position,
                    current_player_kingdom: null,
                    current_location: null,
                    current_enemy_kingdom: foundKingdom[0],
                    current_npc_kingdom: null,
                }, () => {
                    const state = this.component.state;

                    const kingdomTeleportCosts = fetchCost(state.x_position, state.y_position, state.character_position, props.currencies);

                    this.component.setState(kingdomTeleportCosts);
                });
            }
        }
    }

    /**
     * Set the selected npc kingdom data, and it's associated cost.
     *
     * @param data
     */
    setSelectedNPCKingdomData(data: SelectedData) {
        const props = this.component.props;

        if (props.npc_kingdoms !== null) {
            const foundKingdom = props.npc_kingdoms.filter((location: NpcKingdomsDetails) => location.id === data.value);

            if (foundKingdom.length > 0) {
                this.component.setState({
                    x_position: foundKingdom[0].x_position,
                    y_position: foundKingdom[0].y_position,
                    current_player_kingdom: null,
                    current_location: null,
                    current_enemy_kingdom: null,
                    current_npc_kingdom: foundKingdom[0]
                }, () => {
                    const state = this.component.state;

                    const kingdomTeleportCosts = fetchCost(state.x_position, state.y_position, state.character_position, props.currencies);

                    this.component.setState(kingdomTeleportCosts);
                });
            }
        }
    }

    /**
     * Build the coordinate's dropdown data for X and Y.
     *
     * @param data
     */
    buildCoordinatesOptions(data: number[]): SelectedData[] {
        return data.map((d) => {
            return {label: d.toString(), value: d}
        });
    }

    /**
     * Build the: Location select options.
     */
    buildLocationOptions(): SelectedData[]|[] {
        const props = this.component.props;

        if (props.locations !== null) {
            return props.locations.map((location: LocationDetails) => {
                return {label: location.name + ' (X/Y): ' + location.x + '/' + location.y + (location.is_corrupted ? ' (Corrupted)' : ''), value: location.id}
            });
        }

        return [];
    }

    /**
     * Build the: My Kingdoms select options.
     */
    buildMyKingdomsOptions(): SelectedData[]|[] {
        const props = this.component.props;

        if (props.player_kingdoms !== null) {
            return props.player_kingdoms.map((kingdom: PlayerKingdomsDetails) => {
                return {label: kingdom.name + ' (X/Y): ' + kingdom.x_position + '/' + kingdom.y_position, value: kingdom.id}
            });
        }

        return [];
    }

    /**
     * Build the: Enemy Kingdoms select options.
     */
    buildEnemyKingdomOptions(): SelectedData[]|[] {
        const props = this.component.props;

        if (props.enemy_kingdoms !== null) {
            return props.enemy_kingdoms.map((kingdom: PlayerKingdomsDetails) => {
                return {label: kingdom.name + ' (X/Y): ' + kingdom.x_position + '/' + kingdom.y_position, value: kingdom.id}
            });
        }

        return [];
    }

    /**
     * Build the: NPC Kingdoms select options.
     */
    buildNpcKingdomOptions(): SelectedData[]|[] {
        const props = this.component.props;

        if (props.npc_kingdoms !== null) {
            return props.npc_kingdoms.map((kingdom: NpcKingdomsDetails) => {
                return {label: kingdom.name + ' (X/Y): ' + kingdom.x_position + '/' + kingdom.y_position, value: kingdom.id}
            });
        }

        return [];
    }
}
