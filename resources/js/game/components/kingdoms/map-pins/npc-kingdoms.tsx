import React, { Fragment } from "react";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
import NpcKingdomProps, {
    NpcKingdomDefinition,
} from "../types/map/kingdom-pins/npc-kingdom-props";
import KingdomModal from "./modals/kingdom-modal";
import NpcKingdomPin from "./npc-kingdom-pin";

export default class NpcKingdoms extends React.Component<NpcKingdomProps, any> {
    constructor(props: NpcKingdomProps) {
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

    renderKingdomPins() {
        if (this.props.kingdoms == null) {
            return;
        }

        return this.props.kingdoms.map((kingdom: NpcKingdomDefinition) => {
            return (
                <NpcKingdomPin
                    kingdom={kingdom}
                    color={"#e3d60a"}
                    open_kingdom_modal={this.openKingdomModal.bind(this)}
                />
            );
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
                        show_top_section={false}
                    />
                ) : null}
            </Fragment>
        );
    }
}
