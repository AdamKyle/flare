import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import MakeCityACapitalModal from "../modals/make-city-a-capital-modal";

@injectable()
export default class MakeCapitalCityAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public makeCapitalCity(
        component: MakeCityACapitalModal,
        characterId: number,
        kingdomId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/make-capital-city/" + kingdomId + "/" + characterId,
            )
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        success_message: result.data.message,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        loading: false,
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
