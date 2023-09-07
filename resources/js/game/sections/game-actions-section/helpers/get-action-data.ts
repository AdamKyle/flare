import { DateTime } from "luxon";
import { GameActionState } from "../../../lib/game/types/game-state";


function getActionData(actionData: GameActionState): GameActionState {
    let attackTimeOut = actionData.attack_time_out;
    let craftingTimeOut = actionData.crafting_time_out;

    attackTimeOut = getRemainingTime(
        attackTimeOut,
        actionData.attack_time_out_started
    );

    craftingTimeOut = getRemainingTime(
        craftingTimeOut,
        actionData.crafting_time_out_started
    );

    actionData.attack_time_out = attackTimeOut;
    actionData.crafting_time_out = craftingTimeOut;

    return actionData;
}

function getRemainingTime(
    timeLeftInSeconds: number,
    timeStartedInSeconds: number
): number {
    const now = DateTime.local();

    const seconds_left = timeLeftInSeconds;

    const currentTime = now.toSeconds();
    const startTime = timeStartedInSeconds;

    const timeElapsedInSeconds = currentTime - startTime;

    return Math.floor(Math.max(seconds_left - timeElapsedInSeconds, 0));
}

export { getActionData };
