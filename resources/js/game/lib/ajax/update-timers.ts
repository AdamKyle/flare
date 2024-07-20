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
                if (typeof error.response === "undefined") {
                    return;
                }

                const response: AxiosResponse = error.response;

                if (response.status === 401) {
                    return location.reload();
                }
            }
        },
    );
};
