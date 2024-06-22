import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import SmallCouncil from "../capital-city/small-council";

@injectable()
export default class WalkAllKingdomsAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public walkKingdoms(
        component: SmallCouncil,
        characterId: number,
        kingdomId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/walk-all-cities/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        walking_kingdoms: false,
                        success_message: result.data.message,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        walking_kingdoms: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse = error.response;

                        component.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
    }
}
