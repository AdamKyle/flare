import React from "react";
import ViewLocationDetailsModalProps from "../../../../lib/game/types/map/modals/view-location-details-modal-props";
import LocationModal from "../../locations/modals/location-modal";
import KingdomModal from "../../kingdoms/modals/kingdom-modal";
import OtherKingdomModal from "../../kingdoms/modals/other-kingdom-modal";

export default class ViewLocationDetailsModal extends React.Component<ViewLocationDetailsModalProps, any> {

    constructor(props: any) {
        super(props);
    }

    buildModalData() {
        if (this.props.location !== null) {
            return <LocationModal is_open={true}
                                  handle_close={this.props.close_modal}
                                  title={this.props.location.name + ' (X/Y): ' + this.props.location.x + '/' + this.props.location.y}
                                  location={this.props.location}
                                  hide_secondary_button={true} />
        }

        if (this.props.kingdom_id !== null) {
            return <KingdomModal
                is_open={true}
                handle_close={this.props.close_modal}
                kingdom_id={this.props.kingdom_id}
                character_id={this.props.character_id}
                hide_secondary={true} />
        }

        if (this.props.enemy_kingdom_id !== null) {
            return <OtherKingdomModal
                is_open={true}
                handle_close={this.props.close_modal}
                kingdom_id={this.props.enemy_kingdom_id}
                character_id={this.props.character_id}
                hide_secondary={true}
                is_enemy_kingdom={true}
            />
        }

        return null;
    }

    closeModal() {
        this.props.close_modal()
    }

    render() {
        return this.buildModalData();
    }
}
