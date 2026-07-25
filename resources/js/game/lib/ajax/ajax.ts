import AjaxInterface from "./ajax-interface";
import axios, { AxiosError, AxiosHeaders, AxiosResponse, Method } from "axios";
import { injectable } from "tsyringe";
import { handleUnauthenticatedResponse } from "./unauthenticated-response-handler";

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
        const request =
            method.toLowerCase() === "get"
                ? this.getRequest(this.route, this.params)
                : method.toLowerCase() === "post"
                  ? this.postRequest(this.route, this.params)
                  : null;

        request
            ?.then((result: AxiosResponse) => successCallBack(result))
            .catch((error: AxiosError) => {
                if (handleUnauthenticatedResponse(error)) {
                    return;
                }

                return errorCallBack(error);
            });
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
