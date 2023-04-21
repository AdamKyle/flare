import React from "react";
import clsx from "clsx";
import {BattleMessage, BattleMessageType} from "../../../../lib/game/actions/battle/types/battle-message-type";
import {BattleMessageProps} from "./types/battle-message-props";

export default class BattleMesages extends React.Component<BattleMessageProps, {}> {

    constructor(props: BattleMessageProps) {
        super(props);
    }

    typeCheck(battleType: BattleMessageType, type: BattleMessageType): boolean {
        return battleType === type;
    }

    render() {
        if (this.props.is_small && this.props.battle_messages.length > 0) {
            const message = this.props.battle_messages.filter((battleMessage: BattleMessage) => battleMessage.message.includes('resurrect') || battleMessage.message.includes('has been defeated!'))

            if (message.length > 0) {
                return <p className='text-red-500 dark:text-red-400'>{message[0].message}</p>
            } else {
                return <p className='text-blue-500 dark:text-blue-400'>Attack child!</p>
            }
        }

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
