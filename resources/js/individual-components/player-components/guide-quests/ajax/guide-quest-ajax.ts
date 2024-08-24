import { inject, injectable } from "tsyringe";
import Ajax from "../../../../game/lib/ajax/ajax.js";
import AjaxInterface from "../../../../game/lib/ajax/ajax-interface.js";
import GuideQuest from "../modals/guide-quest";
import { AxiosError, AxiosResponse, Method } from "axios";

export enum GUIDE_QUEST_ACTIONS {
    FETCH = "fetch",
    HAND_IN = "hand-in",
}

@injectable()
export default class GuideQuestAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public doGuideQuestAction(
        component: GuideQuest,
        actionType: GUIDE_QUEST_ACTIONS,
        params?: any,
    ) {
        let guideQuestId = 0;

        if (component.state.selected_quest_data_to_show !== null) {
            guideQuestId = component.state.selected_quest_data_to_show.id;
        }

        const route = this.getRoute(
            actionType,
            component.props.user_id,
            guideQuestId,
        );
        const actionForRoute = this.getActionType(actionType);

        if (actionType === GUIDE_QUEST_ACTIONS.FETCH) {
            return this.handleFetchGuideQuest(
                component,
                route,
                actionForRoute,
                params,
            );
        }

        this.handleHandingInGuideQuest(
            component,
            route,
            actionForRoute,
            params,
        );
    }

    protected handleFetchGuideQuest(
        component: GuideQuest,
        route: string,
        action: Method,
        params?: any,
    ): void {
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                (result: AxiosResponse) => {
                    let selectedGuideQuest = null;

                    if (result.data.quests.length <= 1) {
                        selectedGuideQuest = result.data.quests[0];
                    }

                    component.setState({
                        loading: false,
                        quest_data: result.data.quests,
                        can_hand_in: result.data.can_hand_in,
                        completed_requirements:
                            result.data.completed_requirements,
                        selected_quest_data_to_show: selectedGuideQuest,
                    });
                },
                (error: AxiosError) => {
                    console.log(error);
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

    protected handleHandingInGuideQuest(
        component: GuideQuest,
        route: string,
        action: Method,
        params?: any,
    ): void {
        component.setState({ is_handing_in: true });

        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                action,
                (result: AxiosResponse) => {
                    let selectedGuideQuest = null;

                    if (result.data.quests.length <= 1) {
                        selectedGuideQuest = result.data.quests[0];
                    }

                    component.setState({
                        is_handing_in: false,
                        quest_data: result.data.quests,
                        can_hand_in: result.data.can_hand_in,
                        success_message: result.data.message,
                        completed_requirements:
                            result.data.completed_requirements,
                        selected_quest_data_to_show: selectedGuideQuest,
                    });
                },
                (error: AxiosError) => {
                    component.setState({
                        is_handing_in: false,
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

    protected getRoute(
        actionType: GUIDE_QUEST_ACTIONS,
        userId: number,
        guideQuestId?: number,
    ): string {
        switch (actionType) {
            case GUIDE_QUEST_ACTIONS.FETCH:
                return "character/guide-quest/" + userId;
            case GUIDE_QUEST_ACTIONS.HAND_IN:
                return "guide-quests/hand-in/" + userId + "/" + guideQuestId;
            default:
                throw new Error("Unknown route to take.");
        }
    }

    protected getActionType(actionType: GUIDE_QUEST_ACTIONS): Method {
        switch (actionType) {
            case GUIDE_QUEST_ACTIONS.FETCH:
                return "get";
            case GUIDE_QUEST_ACTIONS.HAND_IN:
                return "post";
            default:
                throw new Error("Unknown action to take for route.");
        }
    }
}
