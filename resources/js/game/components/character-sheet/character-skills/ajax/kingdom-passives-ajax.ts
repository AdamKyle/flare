import { inject, injectable } from "tsyringe";
import Ajax from "../../../../lib/ajax/ajax";
import AjaxInterface from "../../../../lib/ajax/ajax-interface";
import KingdomPassives from "../kingdom-passives";
import { AxiosError, AxiosResponse } from "axios";
import TrainPassive from "../skill-tree/modals/train-passive";

@injectable()
export default class KingdomPassivesAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetchPassiveTree(
        component: KingdomPassives,
        characterId: number,
    ): void {
        this.ajax
            .setRoute("character/kingdom-passives/" + characterId)
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    component.setState({
                        kingdom_passives: result.data.kingdom_passives,
                        skill_in_training: result.data.passive_training,
                        loading: false,
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

    public trainPassiveSkill(
        component: TrainPassive,
        characterId: number,
        skillId: number,
    ): void {
        this.ajax
            .setRoute("train/passive/" + skillId + "/" + characterId)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            loading: false,
                        },
                        () => {
                            component.props.manage_success_message(
                                result.data.message,
                            );
                            component.props.update_passives(
                                result.data.kingdom_passives,
                                result.data.passive_training,
                            );
                            component.props.manage_modal();
                        },
                    );
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

    public stopTrainingPassiveSkill(
        component: TrainPassive,
        characterId: number,
        skillId: number,
    ): void {
        this.ajax
            .setRoute("stop-training/passive/" + skillId + "/" + characterId)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            loading: false,
                        },
                        () => {
                            component.props.manage_success_message(
                                result.data.message,
                            );
                            component.props.update_passives(
                                result.data.kingdom_passives,
                                result.data.passive_training,
                            );
                            component.props.manage_modal();
                        },
                    );
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
