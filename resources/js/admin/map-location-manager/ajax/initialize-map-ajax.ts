import { inject, injectable } from "tsyringe";
import MapManager from "../map-manager";
import Ajax from "../../../game/lib/ajax/ajax";
import AjaxInterface from "../../../game/lib/ajax/ajax-interface";
import { AxiosError, AxiosResponse } from "axios";

@injectable()
export default class InitializeMapAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public initializeMap(component: MapManager, mapId: number) {
        this.ajax.setRoute("admin/map-manager/" + mapId).doAjaxCall(
            "get",
            (result: AxiosResponse) => {
                const coordinates = {
                    x: result.data.x_coordinates,
                    y: result.data.y_coordinates,
                };

                component.setState({
                    loading: false,
                    imgSrc: result.data.path,
                    coordinates: coordinates,
                    locations: result.data.locations,
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
