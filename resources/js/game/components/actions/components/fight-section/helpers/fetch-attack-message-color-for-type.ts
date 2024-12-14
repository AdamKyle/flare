import { match } from "ts-pattern";
import { AttackMessageType } from "../enums/attack-message-type";

export const fetchAttackMessageColorForType = (
    type: AttackMessageType,
): string => {
    return match(type)
        .with(
            AttackMessageType.ENEMY_ATTACK,
            () => "text-rose-700 dark:text-rose-500",
        )
        .with(
            AttackMessageType.PLAYER_ATTACK,
            () => "text-emerald-700 dark:text-emerald-500",
        )
        .with(
            AttackMessageType.REGULAR,
            () => "text-danube-700 dark:text-danube-500",
        )
        .otherwise(() => "");
};
