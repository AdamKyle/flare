import { AxiosError, AxiosResponse } from "axios";
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import BuildingsTable from "../buildings/buildings-table";
import GoldBarManagement from "../capital-city/gold-bar-management";

@injectable()
export default class CapitalCityManageGoldBarsAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public depositGoldBars(
        component: GoldBarManagement,
        characterId: number,
        kingdomId: number,
        amountToDeposit: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/deposit-gold-bars/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .setParameters({
                amount_to_purchase: amountToDeposit,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        gold_bar_data: result.data.gold_bar_details,
                        success_message: result.data.message,
                        amount_of_gold_bars_to_buy: 0,
                        processing: false,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        processing: false,
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

    public withdrawGoldBars(
        component: GoldBarManagement,
        characterId: number,
        kingdomId: number,
        amountToWithdraw: number,
    ): void {
        this.ajax
            .setRoute(
                "kingdom/capital-city/withdraw-gold-bars/" +
                    characterId +
                    "/" +
                    kingdomId,
            )
            .setParameters({
                amount_to_withdraw: amountToWithdraw,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        processing: false,
                        gold_bar_data: result.data.gold_bar_details,
                        success_message: result.data.message,
                        amount_of_gold_bars_to_sell: 0,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        processing: false,
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
