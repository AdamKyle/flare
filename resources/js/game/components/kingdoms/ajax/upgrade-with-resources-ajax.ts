import { AxiosError, AxiosResponse } from "axios";
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import UpgradeWithResources from "../buildings/upgrade-with-resources";

@injectable()
export default class UpgradeWithResourcesAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public upgradeBuilding(
        component: UpgradeWithResources,
        characterId: number,
        buildingId: number,
        toLevel: number,
        payingWithGold: boolean,
    ): void {
        this.ajax
            .setRoute(
                "kingdoms/" + characterId + "/upgrade-building/" + buildingId,
            )
            .setParameters({
                to_level: toLevel,
                paying_with_gold: payingWithGold,
            })
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
