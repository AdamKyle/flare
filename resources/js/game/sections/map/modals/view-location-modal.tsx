import React from "react";
import ViewLocationModalProps from "../types/view-location-modal-props";
import LocationDetails from "./components/view-details/location-details";
import KingdomModal from "../../../components/kingdoms/map-pins/modals/kingdom-modal";

export default class ViewLocationModal extends React.Component<
    ViewLocationModalProps,
    any
> {
    constructor(props: ViewLocationModalProps) {
        super(props);
    }

    renderModal() {
        if (this.props.location !== null) {
            return (
                <LocationDetails
                    location={this.props.location}
                    handle_close={this.props.handle_close}
                />
            );
        }

        if (
            this.props.player_kingdom_id !== null ||
            this.props.enemy_kingdom_id !== null ||
            this.props.npc_kingdom_id !== null
        ) {
            let kingdomId =
                this.props.player_kingdom_id !== null
                    ? this.props.player_kingdom_id
                    : this.props.enemy_kingdom_id;

            if (kingdomId === null && this.props.npc_kingdom_id !== null) {
                kingdomId === this.props.npc_kingdom_id;
            }

            if (kingdomId === null) {
                return null;
            }

            return (
                <KingdomModal
                    is_open={true}
                    handle_close={this.props.handle_close}
                    kingdom_id={kingdomId}
                    character_id={this.props.character_id}
                    can_move={true}
                    is_automation_running={true}
                    is_dead={true}
                    show_top_section={true}
                />
            );
        }

        return null;
    }

    render() {
        return this.renderModal();
    }
}
