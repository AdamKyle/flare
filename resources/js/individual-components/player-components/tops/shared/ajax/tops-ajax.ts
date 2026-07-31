import { inject, injectable } from "tsyringe";
import Ajax from "../../../../../game/lib/ajax/ajax.js";
import AjaxInterface from "../../../../../game/lib/ajax/ajax-interface.js";
import { AxiosError, AxiosResponse } from "axios";
import TopsAjaxParams from "../types/tops-ajax-params";
import TopsValue from "../types/tops-value";

@injectable()
export default class TopsAjax {
    constructor(@inject(Ajax) private ajax: AjaxInterface) {}

    public fetch<T>(
        route: string,
        params: TopsAjaxParams,
        success: (data: T) => void,
        failure: (message: string) => void,
    ): void {
        this.ajax
            .setRoute(route)
            .setParameters(params)
            .doAjaxCall(
                "get",
                (result: AxiosResponse<T>) => success(result.data),
                (error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response: AxiosResponse<
                            Record<string, TopsValue>
                        > = error.response;
                        const message = response.data.message;

                        failure(
                            typeof message === "string"
                                ? message
                                : "Unable to load Tops data.",
                        );

                        return;
                    }

                    failure("Unable to load Tops data.");
                },
            );
    }
}
