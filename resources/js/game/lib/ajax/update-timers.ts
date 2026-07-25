import Ajax from "./ajax";
import { AxiosResponse } from "axios";

/**
 * Update the game timers.
 *
 * @param characterId
 */
export const updateTimers = (characterId: number) => {
    new Ajax().setRoute("update-character-timers/" + characterId).doAjaxCall(
        "get",
        (result: AxiosResponse) => {},
        () => {},
    );
};
