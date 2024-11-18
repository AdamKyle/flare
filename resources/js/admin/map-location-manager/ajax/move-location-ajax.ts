import { inject, injectable } from "tsyringe";
import MapManager from "../map-manager";
import Ajax from "../../../game/lib/ajax/ajax";
import AjaxInterface from "../../../game/lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";
import MoveLocationDialogue from "../modals/move-location-dialogue";

@injectable()
export default class MoveLocationAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public moveLocation(
        component: MoveLocationDialogue,
        mapId: number,
        locationId: number,
        npcId: number,
        coordinates: { x: number; y: number },
    ) {
        this.ajax
            .setRoute("admin/map-manager/move/" + mapId)
            .setParameters({
                location_id: locationId,
                npc_id: npcId,
                x: coordinates.x,
                y: coordinates.y,
            })
            .doAjaxCall(
                "post",
                (result: AxiosResponse) => {
                    const coordinates = {
                        x: result.data.x_coordinates,
                        y: result.data.y_coordinates,
                    };

                    component.setState(
                        {
                            processing: false,
                        },
                        () => {
                            component.props.updateLocationsAndNpcs(
                                result.data.locations,
                                result.data.npcs,
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
