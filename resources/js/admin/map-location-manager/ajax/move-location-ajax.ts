import { inject, injectable } from "tsyringe";
import Ajax from "../../../game/lib/ajax/ajax";
import AjaxInterface from "../../../game/lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import MoveLocationDialogue from "../modals/move-location-dialogue";

@injectable()
export default class MoveLocationAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public moveLocation(
        component: MoveLocationDialogue,
        locationId: number,
        coordinates: { x: number; y: number },
    ) {
        this.ajax
            .setRoute("admin/map-manager/move-location/" + locationId)
            .setParameters({
                x: coordinates.x,
                y: coordinates.y,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    component.setState(
                        {
                            processing: false,
                        },
                        () => {
                            component.props.updateLocations(
                                result.data.locations,
                            );
                            component.props.closeModal();
                        },
                    );
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
