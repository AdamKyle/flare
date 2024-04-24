import React from "react";
import KingdomPinProps from "../../map/types/map/kingdom-pins/kingdom-pin-props";

export default class KingdomPin extends React.Component<KingdomPinProps, any> {
    constructor(props: KingdomPinProps) {
        super(props);
    }

    kingdomStyle() {
        return {
            top: this.props.kingdom.y_position,
            left: this.props.kingdom.x_position,
            "--kingdom-color": this.props.kingdom.color,
        };
    }

    openKingdomModal(e: any) {
        this.props.open_kingdom_modal(
            parseInt(e.target.getAttribute("data-kingdom-id")),
        );
    }

    render() {
        return (
            <button
                role="button"
                key={
                    Math.random().toString(36).substring(7) +
                    "-" +
                    this.props.kingdom.id
                }
                data-kingdom-id={this.props.kingdom.id}
                className="kingdom-x-pin"
                style={this.kingdomStyle()}
                onClick={this.openKingdomModal.bind(this)}
            ></button>
        );
    }
}
