import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import FactionNpcTasks from "../faction-npc-tasks";
import { AxiosError, AxiosResponse } from "axios";
import FightData from "./deffinitions/fight-data";

@injectable()
export default class BountyFightAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public doAjaxCall(
        component: FactionNpcTasks,
        fightData: FightData,
        characterId: number,
    ): void {
        this.ajax
            .setRoute("faction-loyalty-bounty/" + characterId)
            .setParameters(fightData)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        attacking: false,
                        success_message: result.data.message,
                        must_revive: result.data.must_revive,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        attacking: false,
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
