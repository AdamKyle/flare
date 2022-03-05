import React from "react";
import KingdomProps from "../../../lib/game/types/map/kingdom-pins/kingdom-props";
import KingdomPin from "./kingdom-pin";

export default class Kingdoms extends React.Component<KingdomProps, any> {

    constructor(props: KingdomProps) {
        super(props);
    }

    renderKingdomPins() {
        if (this.props.kingdoms == null) {
            return;
        }

        return this.props.kingdoms.map((kingdom) => {
            return <KingdomPin kingdom={kingdom} />
        })
    }

    render() {
        return this.renderKingdomPins();
    }
}
