import React, { Fragment } from "react";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import MovePlayer from "../lib/ajax/move-player";
import { canSettleHere } from "../lib/location-helpers";
import ViewLocationState from "../lib/state/view-location-state";
import Conjuration from "../modals/conjuration";
import SetSailModal from "../modals/set-sail-modal";
import SettleKingdomModal from "../modals/settle-kingdom-modal";
import TeleportModal from "../modals/teleport-modal";
import ViewLocationModal from "../modals/view-location-modal";
import LocationDetails from "../types/location-details";
import MapActionsProps from "../types/map-actions-props";
import MapActionsState from "../types/map-actions-state";

export default class MapActions extends React.Component<
    MapActionsProps,
    MapActionsState
> {
    constructor(props: MapActionsProps) {
        super(props);

        this.state = {
            show_location_details: false,
            open_teleport_modal: false,
            open_set_sail: false,
            open_conjure: false,
            open_settle_modal: false,
            player_kingdom_id: null,
            enemy_kingdom_id: null,
            npc_kingdom_id: null,
            location: null,
        };
    }

    componentDidMount() {
        new ViewLocationState(this).updateActionState();
    }

    componentDidUpdate() {
        new ViewLocationState(this).updateActionState();
    }

    teleportPlayer(data: {
        x: number;
        y: number;
        cost: number;
        timeout: number;
    }) {
        new MovePlayer(this).teleportPlayer(
            data,
            this.props.character_id,
            this.props.update_map_state,
        );
    }

    setSail(data: { x: number; y: number; cost: number; timeout: number }) {
        new MovePlayer(this).setSail(
            data,
            this.props.character_id,
            this.props.view_port,
            this.props.update_map_state,
        );
    }

    ports(): LocationDetails[] | [] {
        if (this.props.locations === null) {
            return [];
        }

        return this.props.locations.filter(
            (location: LocationDetails) => location.is_port,
        );
    }

    canSettleKingdom(): boolean {
        return (
            !this.props.can_move ||
            this.props.is_dead ||
            this.props.is_automation_running ||
            !canSettleHere(this)
        );
    }

    canSetSail(): boolean {
        return (
            !this.props.can_move ||
            this.props.is_dead ||
            this.props.is_automation_running ||
            this.props.port_location === null
        );
    }

    canDoAction(): boolean {
        return (
            !this.props.can_move ||
            this.props.is_dead ||
            this.props.is_automation_running
        );
    }

    canViewLocation(): boolean {
        return (
            this.state.location !== null ||
            this.state.player_kingdom_id !== null ||
            this.state.enemy_kingdom_id !== null ||
            this.state.npc_kingdom_id !== null
        );
    }

    manageViewLocation() {
        this.setState({
            show_location_details: !this.state.show_location_details,
        });
    }

    manageTeleportModal() {
        this.setState({
            open_teleport_modal: !this.state.open_teleport_modal,
        });
    }

    manageSetSailModal() {
        this.setState({
            open_set_sail: !this.state.open_set_sail,
        });
    }

    manageConjureModal() {
        this.setState({
            open_conjure: !this.state.open_conjure,
        });
    }

    manageSettleModal() {
        this.setState({
            open_settle_modal: !this.state.open_settle_modal,
        });
    }

    render() {
        return (
            <Fragment>
                <div className="grid lg:grid-cols-5 gap-2">
                    <PrimaryOutlineButton
                        button_label={"View Location Details"}
                        on_click={this.manageViewLocation.bind(this)}
                        disabled={!this.canViewLocation()}
                    />
                    <PrimaryOutlineButton
                        button_label={"Settle Kingdom"}
                        on_click={this.manageSettleModal.bind(this)}
                        disabled={this.canSettleKingdom()}
                    />
                    <PrimaryOutlineButton
                        button_label={"Set Sail"}
                        on_click={this.manageSetSailModal.bind(this)}
                        disabled={this.canSetSail()}
                    />
                    <PrimaryOutlineButton
                        button_label={"Teleport"}
                        on_click={this.manageTeleportModal.bind(this)}
                        disabled={this.canDoAction()}
                    />
                    <PrimaryOutlineButton
                        button_label={"Conjure"}
                        on_click={this.manageConjureModal.bind(this)}
                        disabled={
                            this.canDoAction() ||
                            !this.props.can_engage_celestial
                        }
                    />
                </div>

                {this.state.open_conjure && this.props.can_engage_celestial ? (
                    <Conjuration
                        is_open={this.state.open_conjure}
                        handle_close={this.manageConjureModal.bind(this)}
                        title={"Conjuration"}
                        character_id={this.props.character_id}
                    />
                ) : null}

                {this.state.open_teleport_modal ? (
                    <TeleportModal
                        is_open={this.state.open_teleport_modal}
                        title={"Teleport"}
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
                ) : null}

                {this.state.open_set_sail ? (
                    <SetSailModal
                        is_open={this.state.open_set_sail}
                        set_sail={this.setSail.bind(this)}
                        handle_close={this.manageSetSailModal.bind(this)}
                        title={"Set Sail"}
                        character_position={this.props.character_position}
                        currencies={this.props.character_currencies}
                        ports={this.ports()}
                    />
                ) : null}

                {this.state.open_settle_modal ? (
                    <SettleKingdomModal
                        is_open={this.state.open_settle_modal}
                        handle_close={this.manageSettleModal.bind(this)}
                        character_id={this.props.character_id}
                        map_id={this.props.map_id}
                        can_settle={this.canSettleKingdom()}
                    />
                ) : null}

                {this.state.show_location_details ? (
                    <ViewLocationModal
                        player_kingdom_id={this.state.player_kingdom_id}
                        enemy_kingdom_id={this.state.enemy_kingdom_id}
                        npc_kingdom_id={this.state.npc_kingdom_id}
                        location={this.state.location}
                        handle_close={this.manageViewLocation.bind(this)}
                        character_id={this.props.character_id}
                        can_move={this.props.can_move}
                        is_automation_running={this.props.is_automation_running}
                        is_dead={this.props.is_dead}
                    />
                ) : null}
            </Fragment>
        );
    }
}
