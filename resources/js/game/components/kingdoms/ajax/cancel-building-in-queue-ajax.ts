import { AxiosError, AxiosResponse } from "axios";
import { inject, injectable } from "tsyringe";
import Ajax from "../../../lib/ajax/ajax";
import AjaxInterface from "../../../lib/ajax/ajax-interface";
import BuildingsTable from "../buildings/buildings-table";

@injectable()
export default class CancelBuildingInQueueAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public cancelQueue(component: BuildingsTable, queueId: number): void {
        this.ajax
            .setRoute("kingdoms/building-upgrade/cancel")
            .setParameters({
                queue_id: queueId,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState({
                        success_message: result.data.message,
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
}
