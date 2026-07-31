import { AxiosResponse } from "axios";
import Ajax from "./ajax";

export const updateLocationBasedActions = (characterId: number) => {
    new Ajax()
        .setRoute("map/update-character-location-actions/" + characterId)
        .doAjaxCall(
            "get",
            (result: AxiosResponse) => {},
            () => {},
        );
};
