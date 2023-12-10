import React from "react";
import clsx from "clsx";
import {BattleMessage, BattleMessageType} from "../types/battle-message-type";
import {BattleMessageProps} from "./types/battle-message-props";

export default class BattleMesages extends React.Component<BattleMessageProps, {}> {

    constructor(props: BattleMessageProps) {
        super(props);
    }

    typeCheck(battleType: BattleMessageType, type: BattleMessageType): boolean {
        return battleType === type;
    }

    render() {

        return this.props.battle_messages.map((battleMessage: BattleMessage) => {
            return <p className={clsx(
                {
                    'text-green-700 dark:text-green-400': this.typeCheck(battleMessage.type, 'player-action')
                }, {
                    'text-red-500 dark:text-red-400': this.typeCheck(battleMessage.type, 'enemy-action')
                }, {
                    'text-blue-500 dark:text-blue-400': this.typeCheck(battleMessage.type, 'regular')
                }
            )}>
                {battleMessage.message}
            </p>
        });
    }
}
