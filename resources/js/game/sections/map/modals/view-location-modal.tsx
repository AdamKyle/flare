import React from "react";
import ViewLocationModalProps from "../types/view-location-modal-props";
import LocationDetails from "./components/view-details/location-details";
import KingdomDetails from "./components/view-details/kingdom-details";

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

        if (this.props.player_kingdom_id !== null) {
            return (
                <KingdomDetails
                    kingdom_id={this.props.player_kingdom_id}
                    character_id={this.props.character_id}
                    show_top_section={true}
                    handle_close={this.props.handle_close}
                />
            );
        }

        if (this.props.enemy_kingdom_id !== null) {
            return (
                <KingdomDetails
                    kingdom_id={this.props.enemy_kingdom_id}
                    character_id={this.props.character_id}
                    show_top_section={false}
                    handle_close={this.props.handle_close}
                />
            );
        }

        if (this.props.npc_kingdom_id !== null) {
            return (
                <KingdomDetails
                    kingdom_id={this.props.npc_kingdom_id}
                    character_id={this.props.character_id}
                    show_top_section={false}
                    handle_close={this.props.handle_close}
                />
            );
        }

        return null;
    }

    render() {
        return this.renderModal();
    }
}
