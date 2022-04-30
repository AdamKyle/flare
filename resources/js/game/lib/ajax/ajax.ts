import {Component} from "react";
import AjaxInterface from "./ajax-interface";
import axios, {AxiosError, AxiosResponse, Method} from 'axios';

export default class Ajax implements AjaxInterface {


    private params: Object = {};

    private route: string = '';

    doAjaxCall(method: Method, successCallBack: (result: AxiosResponse) => void, errorCallBack: (error: AxiosError) => void): void {
        if (method === 'get') {
            this.getRequest(this.route, this.params).then((result: AxiosResponse) => {
                return successCallBack(result);
            }).catch((error: AxiosError) => {
                return errorCallBack(error);
            });
        }

        if (method === 'post') {
            this.postRequest(this.route, this.params).then((result: AxiosResponse) => {
                return successCallBack(result);
            }).catch((error: AxiosError) => {
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
        return axios.get('/api/' + url, params);
    }

    postRequest(url: string, params?: any): Promise<AxiosResponse<any>> {
        return axios.post('/api/' + url, params);
    }

}