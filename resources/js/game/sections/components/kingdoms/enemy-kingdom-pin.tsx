import React from "react";
import EnemyKingdomPinProps from "../../map/types/map/kingdom-pins/enemy-kingdom-pin-props";

export default class EnemyKingdomPin extends React.Component<
    EnemyKingdomPinProps,
    {}
> {
    constructor(props: EnemyKingdomPinProps) {
        super(props);
    }

    kingdomStyle() {
        return {
            top: this.props.kingdom.y_position,
            left: this.props.kingdom.x_position,
            "--kingdom-color": this.props.color,
        };
    }

    openKingdomModal(e: any) {
        this.props.open_kingdom_modal(
            parseInt(e.target.getAttribute("data-kingdom-id")),
        );
    }

    render() {
        return (
            <div
                key={
                    Math.random().toString(36).substring(7) +
                    "-" +
                    this.props.kingdom.id
                }
                data-kingdom-id={this.props.kingdom.id}
                className="kingdom-x-pin"
                style={this.kingdomStyle()}
                onClick={this.openKingdomModal.bind(this)}
            ></div>
        );
    }
}
