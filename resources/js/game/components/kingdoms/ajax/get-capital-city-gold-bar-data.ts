import { AxiosError, AxiosResponse } from "axios";
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import GoldBarManagement from "../capital-city/gold-bar-management";

@injectable()
export default class GetCapitalCityGoldBarData {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchData(
        component: GoldBarManagement,
        characterId: number,
        kingdomId: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/" +
                    characterId +
                    "/" +
                    kingdomId +
                    "/gold-bar-details",
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        loading: false,
                        gold_bar_data: result.data.gold_bar_details,
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
