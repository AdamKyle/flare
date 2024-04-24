import Ajax from "./ajax";
import { AxiosError, AxiosResponse } from "axios";

/**
 * Update the game timers.
 *
 * @param characterId
 */
export const updateTimers = (characterId: number) => {
    new Ajax().setRoute("update-character-timers/" + characterId).doAjaxCall(
        "get",
        (result: AxiosResponse) => {},
        (error: AxiosError) => {
            if (error.hasOwnProperty("response")) {
                const response = error.response;

                if (typeof response === "undefined") {
                    return;
                }

                if (response.status === 401) {
                    return location.reload();
                }
            }
        },
    );
};
