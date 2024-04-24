import React, { Fragment } from "react";
import KingdomProps from "../../map/types/map/kingdom-pins/kingdom-props";
import KingdomPin from "./kingdom-pin";
import KingdomModal from "./modals/kingdom-modal";
import KingdomState from "../../map/types/map/kingdom-pins/kingdom-state";
import { viewPortWatcher } from "../../../lib/view-port-watcher";

export default class Kingdoms extends React.Component<
    KingdomProps,
    KingdomState
> {
    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            open_kingdom_modal: false,
            kingdom_id: 0,
            view_port: null,
        };
    }

    componentDidMount() {
        viewPortWatcher(this);
    }

    componentDidUpdate() {
        if (this.state.view_port !== null) {
            if (this.state.view_port < 600 && this.state.open_kingdom_modal) {
                this.setState({
                    kingdom_id: 0,
                    open_kingdom_modal: false,
                });
            }
        }
    }

    openKingdomModal(kingdomId: number) {
        this.setState({
            open_kingdom_modal: true,
            kingdom_id: kingdomId,
        });
    }

    closeKingdomModal() {
        this.setState({
            open_kingdom_modal: false,
            kingdom_id: 0,
        });
    }

    teleportPlayer(data: {
        x: number;
        y: number;
        cost: number;
        timeout: number;
    }) {
        this.props.teleport_player(data);
    }

    renderKingdomPins() {
        if (this.props.kingdoms == null) {
            return;
        }

        return this.props.kingdoms.map((kingdom) => {
            return (
                <KingdomPin
                    kingdom={kingdom}
                    open_kingdom_modal={this.openKingdomModal.bind(this)}
                />
            );
        });
    }

    render() {
        return (
            <Fragment>
                {this.renderKingdomPins()}
                {this.state.open_kingdom_modal ? (
                    <KingdomModal
                        is_open={this.state.open_kingdom_modal}
                        kingdom_id={this.state.kingdom_id}
                        character_id={this.props.character_id}
                        currencies={this.props.currencies}
                        character_position={this.props.character_position}
                        teleport_player={this.teleportPlayer.bind(this)}
                        handle_close={this.closeKingdomModal.bind(this)}
                        can_move={this.props.can_move}
                        is_automation_running={this.props.is_automation_running}
                        is_dead={this.props.is_dead}
                        show_top_section={true}
                    />
                ) : null}
            </Fragment>
        );
    }
}
