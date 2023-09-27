import { DateTime } from "luxon";
import { GameActionState } from "../../../lib/game/types/game-state";
import { getRemainingTime } from "../../../lib/helpers/time-left-seconds";


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



export { getActionData };
