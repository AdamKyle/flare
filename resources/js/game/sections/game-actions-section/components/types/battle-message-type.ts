export type BattleMessage = {
    message: string;
    type: BattleMessageType;
};

export type BattleMessageType = "regular" | "player-action" | "enemy-action";
