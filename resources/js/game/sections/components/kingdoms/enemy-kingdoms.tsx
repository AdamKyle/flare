import React, {Fragment} from "react";
import EnemyKingdomPin from "./enemy-kingdom-pin";
import KingdomProps from "../../../lib/game/types/map/kingdom-pins/kingdom-props";
import OtherKingdomModal from "./modals/other-kingdom-modal";
import KingdomState from "../../../lib/game/types/map/kingdom-pins/kingdom-state";
import {viewPortWatcher} from "../../../lib/view-port-watcher";

export default class EnemyKingdoms extends React.Component<KingdomProps, KingdomState> {

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            open_kingdom_modal: false,
            kingdom_id: 0,
            view_port: null,
        }
    }

    componentDidMount() {
        viewPortWatcher(this);
    }

    componentDidUpdate() {
        if (this.state.view_port !== null) {
            if (this.state.view_port < 1600 && this.state.open_kingdom_modal) {
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

    teleportPlayer(data: {x: number, y: number, cost: number, timeout: number}) {
        this.props.teleport_player(data);
    }

    renderKingdomPins() {
        if (this.props.kingdoms == null) {
            return;
        }

        return this.props.kingdoms.map((kingdom) => {
            if (this.props.character_id !== kingdom.character_id) {
                return <EnemyKingdomPin kingdom={kingdom} color={'#e82b13'} open_kingdom_modal={this.openKingdomModal.bind(this)}/>
            }
        });
    }

    render() {
        return (
            <Fragment>
                {this.renderKingdomPins()}

                {
                    this.state.open_kingdom_modal ?
                        <OtherKingdomModal is_open={this.state.open_kingdom_modal}
                                           kingdom_id={this.state.kingdom_id}
                                           character_id={this.props.character_id}
                                           currencies={this.props.currencies}
                                           character_position={this.props.character_position}
                                           teleport_player={this.teleportPlayer.bind(this)}
                                           handle_close={this.closeKingdomModal.bind(this)}
                                           is_enemy_kingdom={true}
                                           hide_secondary={false}
                        />
                        : null
                }
            </Fragment>
        );
    }
}
