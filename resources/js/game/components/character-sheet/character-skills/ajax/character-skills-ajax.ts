import { inject, injectable } from "tsyringe";
import Ajax from "../../../../lib/ajax/ajax";
import AjaxInterface from "../../../../lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import TrainSkill from "../modals/train-skill";
import Skills from "../skills";

@injectable()
export default class CharacterSkillsAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public trainSkill(
        component: TrainSkill,
        characterId: number,
        skillId: number,
        xpPercentage: number,
    ): void {
        this.ajax
            .setRoute("skill/train/" + characterId)
            .setParameters({
                skill_id: skillId,
                xp_percentage: xpPercentage,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            loading: false,
                        },
                        () => {
                            component.props.set_success_message(
                                result.data.message,
                            );
                            component.props.update_skills(result.data.skills);
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

    public stopTrainingSkill(
        component: Skills,
        characterId: number,
        skillId: number,
    ): void {
        this.ajax
            .setRoute("skill/cancel-train/" + characterId + "/" + skillId)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            stopping: false,
                            success_message: result.data.message,
                        },
                        () => {
                            component.props.update_skills(result.data.skills);
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
