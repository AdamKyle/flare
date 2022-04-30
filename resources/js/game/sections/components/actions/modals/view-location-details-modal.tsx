import React from "react";
import ViewLocationDetailsModalProps from "../../../../lib/game/types/map/modals/view-location-details-modal-props";
import LocationModal from "../../locations/modals/location-modal";
import KingdomModal from "../../kingdoms/modals/kingdom-modal";
import OtherKingdomModal from "../../kingdoms/modals/other-kingdom-modal";
import {viewPortWatcher} from "../../../../lib/view-port-watcher";

export default class ViewLocationDetailsModal extends React.Component<ViewLocationDetailsModalProps, any> {

    constructor(props: any) {
        super(props);

        this.state = {
            view_port: null,
            is_open: true
        }
    }

    componentDidMount() {
        console.log('hello?');
        viewPortWatcher(this);
    }

    componentDidUpdate() {
        console.log(this.state.view_port);
        if (this.state.view_port !== null) {
            if (this.state.view_port < 1600) {
                this.setState({
                    is_open: false,
                }, () => {
                    this.props.close_modal();
                })
            }
        }
    }

    buildModalData() {
        if (this.props.location !== null && this.state.is_open) {
            return <LocationModal is_open={this.state.is_open}
                                  handle_close={this.props.close_modal}
                                  title={this.props.location.name + ' (X/Y): ' + this.props.location.x + '/' + this.props.location.y}
                                  location={this.props.location}
                                  hide_secondary_button={true} />
        }

        if (this.props.kingdom_id !== null && this.state.is_open) {
            return <KingdomModal
                is_open={this.state.is_open}
                handle_close={this.props.close_modal}
                kingdom_id={this.props.kingdom_id}
                character_id={this.props.character_id}
                hide_secondary={true} />
        }

        if (this.props.enemy_kingdom_id !== null && this.state.is_open) {
            return <OtherKingdomModal
                is_open={this.state.is_open}
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