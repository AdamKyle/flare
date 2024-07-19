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
                    const response: AxiosResponse = error.response;

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
