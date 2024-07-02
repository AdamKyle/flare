import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import FactionNpcTasks from "../faction-npc-tasks";
import { AxiosError, AxiosResponse } from "axios";
import CraftingData from "./deffinitions/crafting-data";

@injectable()
export default class HandleCraftingAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public doAjaxCall(
        component: FactionNpcTasks,
        craftingData: CraftingData,
        characterId: number,
    ): void {
        this.ajax
            .setRoute("craft/" + characterId)
            .setParameters(craftingData)
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            crafting: false,
                        },
                        () => {
                            if (result.data.crafted_item) {
                                component.setState({
                                    success_message:
                                        "The Npc is joyful that you were able to craft the item! (Check Server Messages for more details and additional messages. On mobile, you can select Server Messages from the Orange Chat Tabs drop down below)",
                                });
                            }

                            if (!result.data.crafted_item) {
                                component.setState({
                                    success_message:
                                        "The Npc is confused. You failed. Why? (See the server message section below to find out why. For mobile, click the Chat Tabs button and select Server Messages.)",
                                });
                            }
                        },
                    );
                },
                (error: AxiosError) => {
                    component.setState({
                        crafting: false,
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
