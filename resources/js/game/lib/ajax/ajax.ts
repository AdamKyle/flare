import AjaxInterface from "./ajax-interface";
import axios, { AxiosError, AxiosResponse, Method } from "axios";
import { injectable } from "tsyringe";

@injectable()
export default class Ajax implements AjaxInterface {
    private params: Object = {};

    private route: string = "";

    doAjaxCall(
        method: Method,
        successCallBack: (result: AxiosResponse) => void,
        errorCallBack: (error: AxiosError) => void,
    ): void {
        if (method.toLowerCase() === "get") {
            this.getRequest(this.route, this.params)
                .then((result: AxiosResponse) => {
                    return successCallBack(result);
                })
                .catch((error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response = error.response;

                        if (response.status === 401) {
                            window.location.reload();
                        }

                        if (response.status === 429) {
                            this.initiateGlobalTimeOut();
                        }
                    }

                    return errorCallBack(error);
                });
        }

        if (method.toLowerCase() === "post") {
            this.postRequest(this.route, this.params)
                .then((result: AxiosResponse) => {
                    return successCallBack(result);
                })
                .catch((error: AxiosError) => {
                    if (typeof error.response !== "undefined") {
                        const response = error.response;

                        if (response.status === 401) {
                            window.location.reload();
                        }

                        if (response.status === 429) {
                            this.initiateGlobalTimeOut();
                        }
                    }

                    return errorCallBack(error);
                });
        }
    }

    setParameters(params: Object): AjaxInterface {
        this.params = params;

        return this;
    }

    setRoute(route: string): AjaxInterface {
        this.route = route;

        return this;
    }

    getRequest(url: string, params?: any): Promise<AxiosResponse<any>> {
        return axios.get("/api/" + url, { params: params });
    }

    postRequest(url: string, params?: any): Promise<AxiosResponse<any>> {
        return axios.post("/api/" + url, params);
    }

    initiateGlobalTimeOut() {
        this.setRoute("character-timeout").doAjaxCall(
            "post",
            (result: AxiosResponse) => {},
            (error: AxiosError) => {
                console.error(error);
            },
        );
    }
}
