import React, {Fragment} from "react";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import MapActionsProps from "../../../lib/game/map/types/map-actions-props";
import {canSettleHere} from "../../../lib/game/map/location-helpers";
import MapActionsState from "../../../lib/game/map/types/map-actions-state";
import ViewLocationState from "../../../lib/game/map/state/view-location-state";
import TeleportModal from "../../components/actions/modals/teleport-modal";
import MovePlayer from "../../../lib/game/map/ajax/move-player";

export default class MapActions extends React.Component<MapActionsProps, MapActionsState> {
    constructor(props: MapActionsProps) {
        super(props);

        this.state = {
            show_location_details: false,
            open_teleport_modal: false,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
            npc_kingdom_id: null,
            location: null,
        }
    }

    componentDidMount() {
        (new ViewLocationState(this)).updateActionState();
    }

    componentDidUpdate() {
        (new ViewLocationState(this)).updateActionState();
    }

    teleportPlayer(data: {x: number, y: number, cost: number, timeout: number}) {
        (new MovePlayer(this)).teleportPlayer(data, this.props.character_id, this.props.update_map_state);
    }

    canSettleKingdom() {
        return !this.props.can_move || this.props.is_dead || this.props.is_automation_running || !canSettleHere(this);
    }

    manageViewLocation() {
        this.setState({
            show_location_details: !this.state.show_location_details,
        })
    }

    manageTeleportModal() {
        this.setState({
            open_teleport_modal: !this.state.open_teleport_modal,
        })
    }

    render() {
        return (
            <Fragment>
                <div className='grid grid-cols-4 gap-2'>
                    <PrimaryOutlineButton button_label={'View Location Details'}
                                          on_click={this.manageViewLocation.bind(this)} />
                    <PrimaryOutlineButton button_label={'Settle Kingdom'}
                                          on_click={() => {}}
                                          disabled={this.canSettleKingdom()}/>
                    <PrimaryOutlineButton button_label={'Set Sail'}
                                          on_click={() => {}}
                                          disabled={!this.props.can_move || this.props.is_dead || this.props.is_automation_running || this.props.port_location === null}/>
                    <PrimaryOutlineButton button_label={'Teleport'}
                                          on_click={this.manageTeleportModal.bind(this)}
                                          disabled={!this.props.can_move || this.props.is_dead || this.props.is_automation_running}/>
                </div>

                {
                    this.state.open_teleport_modal ?
                        <TeleportModal is_open={this.state.open_teleport_modal}
                                       title={'Teleport'}
                                       teleport_player={this.teleportPlayer.bind(this)}
                                       handle_close={this.manageTeleportModal.bind(this)}
                                       coordinates={this.props.coordinates}
                                       character_position={this.props.character_position}
                                       currencies={this.props.character_currencies}
                                       view_port={this.props.view_port}
                                       locations={this.props.locations}
                                       player_kingdoms={this.props.player_kingdoms}
                                       enemy_kingdoms={this.props.enemy_kingdoms}
                                       npc_kingdoms={this.props.npc_kingdoms}
                        />
                    : null
                }
            </Fragment>
        )
    }
}
