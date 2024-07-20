import { AxiosError, AxiosResponse } from "axios";
import Ajax from "./ajax";

export const updateLocationBasedActions = (characterId: number) => {
    new Ajax()
        .setRoute("map/update-character-location-actions/" + characterId)
        .doAjaxCall(
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
