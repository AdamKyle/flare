import {BattleMessage} from "./types/battle-message-type";

export default class BattleBase {

    protected battle_messages: BattleMessage[];

    constructor() {
        this.battle_messages = [];
    }

    getMessages(): BattleMessage[] {
        return this.battle_messages;
    }

    resetMessages() {
        this.battle_messages = [];
    }

    addMessage(message: string, type: 'regular' | 'player-action' | 'enemy-action'): void {
        this.battle_messages.push({
            message: message,
            type: type,
        })
    }

    mergeMessages(messagesToMerge: BattleMessage[]) {
        this.battle_messages = [...this.battle_messages, ...messagesToMerge];
    }
}
