import AjaxInterface from "./ajax-interface";
import axios, { AxiosError, AxiosHeaders, AxiosResponse, Method } from "axios";
import { injectable } from "tsyringe";

@injectable()
export default class Ajax implements AjaxInterface {
    private params: Object = {};

    private route: string = "";

    private headers: AxiosHeaders = new AxiosHeaders({
        "Content-Type": "application/json",
    });

    doAjaxCall(
        method: Method,
        successCallBack: (result: AxiosResponse) => void,
        errorCallBack: (error: AxiosError) => void,
    ): void {
        if (method.toLowerCase() === "get") {
            this.getRequest(this.route, this.params)
                .then((result: AxiosResponse) => successCallBack(result))
                .catch((error: AxiosError) => {
                    if (error.response) {
                        const response: AxiosResponse = error.response;

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
                .then((result: AxiosResponse) => successCallBack(result))
                .catch((error: AxiosError) => {
                    if (error.response) {
                        const response: AxiosResponse = error.response;

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

    setAdditionalHeaders(headers: Partial<AxiosHeaders>): AjaxInterface {
        this.headers = new AxiosHeaders({
            ...this.headers,
            ...headers,
        });

        return this;
    }

    getRequest(url: string, params?: any): Promise<AxiosResponse<any>> {
        return axios.get("/api/" + url, { params: params });
    }

    postRequest(url: string, params?: any): Promise<AxiosResponse<any>> {
        return axios.post("/api/" + url, params, { headers: this.headers });
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
