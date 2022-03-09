import React, {Fragment} from "react";
import KingdomProps from "../../../lib/game/types/map/kingdom-pins/kingdom-props";
import KingdomPin from "./kingdom-pin";
import KingdomModal from "./modals/kingdom-modal";

export default class Kingdoms extends React.Component<KingdomProps, any> {

    constructor(props: KingdomProps) {
        super(props);

        this.state = {
            open_kingdom_modal: false,
            kingdom_id: 0,
        }
    }

    openKingdomModal(kingdomId: number) {
        this.setState({
            open_kingdom_modal: true,
            kingdomId: kingdomId,
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
            return (
                <KingdomPin kingdom={kingdom} open_kingdom_modal={this.openKingdomModal.bind(this)}/>
            )
        })
    }

    render() {
        return (
            <Fragment>
                {this.renderKingdomPins()}
                {
                    this.state.open_kingdom_modal ?
                        <KingdomModal is_open={this.state.open_kingdom_modal}
                                      kingdom_id={this.state.kingdomId}
                                      character_id={this.props.character_id}
                                      currencies={this.props.currencies}
                                      character_position={this.props.character_position}
                                      teleport_player={this.teleportPlayer.bind(this)}
                                      handle_close={this.closeKingdomModal.bind(this)}
                        />
                        : null
                }
            </Fragment>
        );
    }
}
