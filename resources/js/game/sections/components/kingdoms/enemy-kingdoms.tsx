import React from "react";
import EnemyKingdomPin from "./enemy-kingdom-pin";
import KingdomProps from "../../../lib/game/types/map/kingdom-pins/kingdom-props";

export default class EnemyKingdoms extends React.Component<KingdomProps, any> {

    constructor(props: KingdomProps) {
        super(props);
    }

    renderKingdomPins() {
        if (this.props.kingdoms == null) {
            return;
        }

        return this.props.kingdoms.map((kingdom) => {
            if (this.props.character_id !== kingdom.character_id) {
                return <EnemyKingdomPin kingdom={kingdom} color={'#e82b13'}/>
            }
        });
    }

    render() {
        return this.renderKingdomPins();
    }
}
