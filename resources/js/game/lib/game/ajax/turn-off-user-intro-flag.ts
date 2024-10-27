import { AxiosError, AxiosResponse } from "axios";
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import Game from "../../../game";

@injectable()
export default class TurnOffUserIntroFlag {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public turnOffIntro(component: Game, characterId: number): void {
        this.ajax
            .setRoute("update-player-flags/turn-off-intro/" + characterId)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {},
                (error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        console.error(response.data);
                    }
                },
            );
    }
}
