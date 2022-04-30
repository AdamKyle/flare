import React from "react";
import EnemyKingdomPin from "./enemy-kingdom-pin";
import KingdomProps from "../../../lib/game/types/map/kingdom-pins/kingdom-props";
import NpcKingdomPin from "./npc-kingdom-pin";
import NpcKingdomProps from "../../../lib/game/types/map/kingdom-pins/npc-kingdom-props";
import NpcKingdomPinProps from "../../../lib/game/types/map/kingdom-pins/npc-kingdom-pin-props";
import {viewPortWatcher} from "../../../lib/view-port-watcher";

export default class NpcKingdoms extends React.Component<NpcKingdomProps, any> {

    constructor(props: NpcKingdomProps) {
        super(props);
    }

    renderKingdomPins() {
        if (this.props.kingdoms == null) {
            return;
        }

        return this.props.kingdoms.map((kingdom) => {
            return <NpcKingdomPin kingdom={kingdom} color={'#e3d60a'}/>
        });
    }

    render() {
        return this.renderKingdomPins();
    }
}
