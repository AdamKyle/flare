import NpcKingdomsDetails from "../../../../components/kingdoms/types/map/npc-kingdoms-details";
import PlayerKingdomsDetails from "../../../../components/kingdoms/types/map/player-kingdoms-details";
import TeleportModal from "../../modals/teleport-modal";
import LocationDetails from "../../types/location-details";

export default class ManageTeleportModalState {
    private component: TeleportModal;

    constructor(component: TeleportModal) {
        this.component = component;
    }

    /**
     * Update the teleport modals state based on where the character is.
     */
    public updateTeleportModalState() {
        this.setCurrentLocation();
        this.setPlayerKingdom();
        this.setEnemyKingdom();
        this.setNPCKingdom();
    }

    /**
     * Set the current location.
     *
     * @private
     */
    private setCurrentLocation() {
        const props = this.component.props;
        const state = this.component.state;

        if (props.locations !== null && state.current_location === null) {
            const foundLocation = props.locations.filter(
                (location: LocationDetails) =>
                    location.x === props.character_position.x &&
                    location.y === props.character_position.y,
            );

            if (foundLocation.length > 0) {
                this.component.setState({
                    current_location: foundLocation[0],
                });
            }
        }
    }

    /**
     * Set the player kingdom.
     *
     * @private
     */
    private setPlayerKingdom() {
        const props = this.component.props;
        const state = this.component.state;

        if (
            props.player_kingdoms !== null &&
            state.current_player_kingdom === null
        ) {
            const foundKingdom = props.player_kingdoms.filter(
                (kingdom: PlayerKingdomsDetails) =>
                    kingdom.x_position === props.character_position.x &&
                    kingdom.y_position === props.character_position.y,
            );

            if (foundKingdom.length > 0) {
                this.component.setState({
                    current_player_kingdom: foundKingdom[0],
                });
            }
        }
    }

    /**
     * Set the enemy kingdom
     *
     * @private
     */
    private setEnemyKingdom() {
        const props = this.component.props;
        const state = this.component.state;

        if (
            props.enemy_kingdoms !== null &&
            state.current_enemy_kingdom === null
        ) {
            const foundKingdom = props.enemy_kingdoms.filter(
                (kingdom: PlayerKingdomsDetails) =>
                    kingdom.x_position === props.character_position.x &&
                    kingdom.y_position === props.character_position.y,
            );

            if (foundKingdom.length > 0) {
                this.component.setState({
                    current_enemy_kingdom: foundKingdom[0],
                });
            }
        }
    }

    /**
     * Set the npc kingdom
     *
     * @private
     */
    private setNPCKingdom() {
        const props = this.component.props;
        const state = this.component.state;

        if (props.npc_kingdoms !== null && state.current_npc_kingdom === null) {
            const foundKingdom = props.npc_kingdoms.filter(
                (kingdom: NpcKingdomsDetails) =>
                    kingdom.x_position === props.character_position.x &&
                    kingdom.y_position === props.character_position.y,
            );

            if (foundKingdom.length > 0) {
                this.component.setState({
                    current_npc_kingdom: foundKingdom[0],
                });
            }
        }
    }
}
