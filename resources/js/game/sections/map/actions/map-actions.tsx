import React, {Fragment} from "react";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import MapActionsProps from "../../../lib/game/map/types/map-actions-props";
import {canSettleHere} from "../../../lib/game/map/location-helpers";
import MapActionsState from "../../../lib/game/map/types/map-actions-state";
import ViewLocationState from "../../../lib/game/map/state/view-location-state";
import TeleportModal from "../../components/actions/modals/teleport-modal";
import MovePlayer from "../../../lib/game/map/ajax/move-player";
import SetSailModal from "../../components/actions/modals/set-sail-modal";
import LocationDetails from "../../../lib/game/map/types/location-details";
import Conjuration from "../../components/actions/modals/conjuration";

export default class MapActions extends React.Component<MapActionsProps, MapActionsState> {
    constructor(props: MapActionsProps) {
        super(props);

        this.state = {
            show_location_details: false,
            open_teleport_modal: false,
            open_set_sail: false,
            open_conjure: false,
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

    setSail(data: {x: number, y: number, cost: number, timeout: number}) {
        (new MovePlayer(this)).setSail(data, this.props.character_id, this.props.view_port, this.props.update_map_state);
    }

    ports(): LocationDetails[]|[] {
        return this.props.locations.filter((location: LocationDetails) => location.is_port);
    }

    canSettleKingdom(): boolean {
        return !this.props.can_move || this.props.is_dead || this.props.is_automation_running || !canSettleHere(this);
    }

    canSetSail(): boolean {
        return !this.props.can_move || this.props.is_dead || this.props.is_automation_running || this.props.port_location === null;
    }

    canDoAction(): boolean {
        return !this.props.can_move || this.props.is_dead || this.props.is_automation_running;
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

    manageSetSailModal() {
        this.setState({
            open_set_sail: !this.state.open_set_sail,
        })
    }

    manageConjureModal() {
        this.setState({
            open_conjure: !this.state.open_conjure,
        })
    }

    render() {
        return (
            <Fragment>
                <div className='grid grid-cols-5 gap-2'>
                    <PrimaryOutlineButton button_label={'View Location Details'}
                                          on_click={this.manageViewLocation.bind(this)} />
                    <PrimaryOutlineButton button_label={'Settle Kingdom'}
                                          on_click={() => {}}
                                          disabled={this.canSettleKingdom()}/>
                    <PrimaryOutlineButton button_label={'Set Sail'}
                                          on_click={this.manageSetSailModal.bind(this)}
                                          disabled={this.canSetSail()}/>
                    <PrimaryOutlineButton button_label={'Teleport'}
                                          on_click={this.manageTeleportModal.bind(this)}
                                          disabled={this.canDoAction()}/>
                    <PrimaryOutlineButton button_label={'Conjure'}
                                          on_click={this.manageConjureModal.bind(this)}
                                          disabled={this.canDoAction()}/>
                </div>

                {
                    this.state.open_conjure ?
                        <Conjuration is_open={this.state.open_conjure}
                                     handle_close={this.manageConjureModal.bind(this)}
                                     title={'Conjuration'}
                                     character_id={this.props.character_id}
                        />
                    : null
                }

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

                {
                    this.state.open_set_sail ?
                        <SetSailModal  is_open={this.state.open_set_sail}
                                       set_sail={this.setSail.bind(this)}
                                       handle_close={this.manageSetSailModal.bind(this)}
                                       title={'Set Sail'}
                                       character_position={this.props.character_position}
                                       currencies={this.props.character_currencies}
                                       ports={this.ports()}
                        />
                        : null
                }
            </Fragment>
        )
    }
}
