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
        viewPortWatcher(this);
    }

    componentDidUpdate() {
        if (this.state.view_port !== null) {
            if (this.state.view_port < 1600 && !this.props.is_small_screen) {
                this.setState({
                    is_open: false,
                }, () => {
                    this.closeModal();
                })
            }
        }
    }

    buildModalData() {
        if (this.props.location !== null && this.state.is_open) {
            return <LocationModal is_open={this.state.is_open}
                                  handle_close={this.closeModal.bind(this)}
                                  title={this.props.location.name + ' (X/Y): ' + this.props.location.x + '/' + this.props.location.y}
                                  location={this.props.location}
                                  hide_secondary_button={true}
                                  can_move={this.props.can_move}
                                  is_automation_running={this.props.is_automation_running}
                                  is_dead={this.props.is_dead}
            />
        }

        if (this.props.kingdom_id !== null && this.state.is_open) {
            return <KingdomModal
                is_open={this.state.is_open}
                handle_close={this.closeModal.bind(this)}
                kingdom_id={this.props.kingdom_id}
                character_id={this.props.character_id}
                hide_secondary={false}
                can_move={this.props.can_move}
                is_automation_running={this.props.is_automation_running}
                is_dead={this.props.is_dead}
            />
        }

        if ((this.props.enemy_kingdom_id !== null || this.props.npc_kingdom_id !== null) && this.state.is_open) {
            let kingdomId: number = 0;

            if (this.props.enemy_kingdom_id !== null) {
                kingdomId = this.props.enemy_kingdom_id;
            } else if (this.props.npc_kingdom_id !== null) {
                kingdomId = this.props.npc_kingdom_id;
            }

            return <OtherKingdomModal
                is_open={this.state.is_open}
                handle_close={this.closeModal.bind(this)}
                kingdom_id={kingdomId}
                character_id={this.props.character_id}
                hide_secondary={true}
                is_enemy_kingdom={true}
                can_move={this.props.can_move}
                is_automation_running={this.props.is_automation_running}
                is_dead={this.props.is_dead}
            />
        }

        return null;
    }

    closeModal() {
        console.log('Am I ever called?');
        this.setState({
            is_open: false,
        }, () => {
            console.log('I should be here....');
            this.props.close_modal()
        })

    }

    render() {
        return this.buildModalData();
    }
}
