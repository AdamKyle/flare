import React from "react";
import EnemyKingdomPinProps from "../../../lib/game/types/map/kingdom-pins/enemy-kingdom-pin-props";
import NpcKingdomProps from "../../../lib/game/types/map/kingdom-pins/npc-kingdom-props";
import NpcKingdomPinProps from "../../../lib/game/types/map/kingdom-pins/npc-kingdom-pin-props";

export default class NpcKingdomPin extends React.Component<NpcKingdomPinProps, {}> {

    constructor(props: NpcKingdomPinProps) {
        super(props);
    }

    kingdomStyle() {
        return {
            top: this.props.kingdom.y_position,
            left: this.props.kingdom.x_position,
            '--kingdom-color': this.props.color
        };
    }

    openKingdomModal(e: any) {
        console.log(parseInt(e.target.getAttribute('data-location-id')))
    }

    render() {
        return (
            <div
                key={this.props.kingdom.id}
                data-kingdom-id={this.props.kingdom.id}
                className="kingdom-x-pin"
                style={this.kingdomStyle()}
                onClick={this.openKingdomModal.bind(this)}
            >
            </div>
        );
    }
}
